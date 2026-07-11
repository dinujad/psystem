<?php

namespace App\Http\Controllers;

use App\Contact;
use App\DeliveryParcel;
use App\Services\FardarDeliveryService;
use App\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeliveryController extends Controller
{
    public function __construct(protected FardarDeliveryService $fardar)
    {
    }

    protected function checkAccess(): void
    {
        if (
            auth()->user()->can('access_shipping')
            || auth()->user()->can('access_own_shipping')
            || auth()->user()->can('sell.view')
            || auth()->user()->can('send_notifications')
        ) {
            return;
        }

        abort(403, 'Unauthorized.');
    }

    public function index(Request $request)
    {
        $this->checkAccess();

        $businessId = session('user.business_id');
        $search = trim((string) $request->get('q', ''));
        $status = $request->get('status', 'all');

        $query = DeliveryParcel::with(['transaction', 'creator'])
            ->where('business_id', $businessId)
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('waybill_no', 'like', "%{$search}%")
                    ->orWhere('order_id', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_contact_1', 'like', "%{$search}%")
                    ->orWhere('recipient_city', 'like', "%{$search}%");
            });
        }

        if ($status === 'pending') {
            $query->where(function ($q) {
                $q->whereNull('current_status')
                    ->orWhere('current_status', '')
                    ->orWhere('current_status', 'pending');
            });
        } elseif ($status === 'delivered') {
            $query->where('current_status', 'like', '%deliver%');
        } elseif ($status === 'transit') {
            $query->whereNotNull('current_status')
                ->where('current_status', '!=', '')
                ->where('current_status', '!=', 'pending')
                ->where('current_status', 'not like', '%deliver%')
                ->where('current_status', 'not like', '%cancel%')
                ->where('current_status', 'not like', '%return%');
        } elseif ($status === 'failed') {
            $query->where(function ($q) {
                $q->where('current_status', 'like', '%cancel%')
                    ->orWhere('current_status', 'like', '%return%')
                    ->orWhere('current_status', 'like', '%fail%')
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('api_status_code')
                            ->where('api_status_code', '!=', 200);
                    });
            });
        }

        $parcels = $query->paginate(25)->appends($request->query());

        $base = DeliveryParcel::where('business_id', $businessId);
        $statusCounts = [
            'all' => (clone $base)->count(),
            'pending' => (clone $base)->where(function ($q) {
                $q->whereNull('current_status')
                    ->orWhere('current_status', '')
                    ->orWhere('current_status', 'pending');
            })->count(),
            'transit' => (clone $base)->whereNotNull('current_status')
                ->where('current_status', '!=', '')
                ->where('current_status', '!=', 'pending')
                ->where('current_status', 'not like', '%deliver%')
                ->where('current_status', 'not like', '%cancel%')
                ->where('current_status', 'not like', '%return%')
                ->count(),
            'delivered' => (clone $base)->where('current_status', 'like', '%deliver%')->count(),
            'failed' => (clone $base)->where(function ($q) {
                $q->where('current_status', 'like', '%cancel%')
                    ->orWhere('current_status', 'like', '%return%')
                    ->orWhere('current_status', 'like', '%fail%')
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('api_status_code')
                            ->where('api_status_code', '!=', 200);
                    });
            })->count(),
        ];

        return view('delivery.index', [
            'parcels' => $parcels,
            'search' => $search,
            'status' => $status,
            'statusCounts' => $statusCounts,
            'fardarConfigured' => $this->fardar->isConfigured(),
        ]);
    }

    public function create(Request $request)
    {
        $this->checkAccess();

        $businessId = session('user.business_id');
        $transaction = null;
        $defaults = [
            'waybill_mode' => 'new',
            'waybill_id' => '',
            'order_id' => '',
            'parcel_weight' => '1',
            'parcel_description' => 'PrintWorks order',
            'recipient_name' => '',
            'recipient_contact_1' => '',
            'recipient_contact_2' => '',
            'recipient_address' => '',
            'recipient_city' => '',
            'amount' => '0',
            'exchange' => '0',
            'transaction_id' => null,
        ];

        if ($request->filled('transaction_id')) {
            $transaction = Transaction::with('contact')
                ->where('business_id', $businessId)
                ->where('type', 'sell')
                ->findOrFail($request->transaction_id);

            $contact = $transaction->contact;
            $defaults['transaction_id'] = $transaction->id;
            $defaults['order_id'] = preg_replace('/\D+/', '', (string) ($transaction->invoice_no ?: $transaction->id)) ?: (string) $transaction->id;
            $defaults['amount'] = (string) ($transaction->final_total ?? 0);
            $defaults['parcel_description'] = 'Invoice '.($transaction->invoice_no ?: $transaction->id);

            if ($contact) {
                $defaults['recipient_name'] = $contact->name
                    ?: trim(($contact->supplier_business_name ?? '').' '.($contact->first_name ?? ''));
                $defaults['recipient_contact_1'] = $contact->mobile ?: ($contact->landline ?? '');
                $defaults['recipient_contact_2'] = $contact->alternate_number ?? '';
                $defaults['recipient_city'] = $contact->city ?? '';
                $defaults['recipient_address'] = $this->buildAddress($contact, $transaction);
            } elseif (! empty($transaction->shipping_address)) {
                $defaults['recipient_address'] = $transaction->shipping_address;
            }

            if (! empty($transaction->delivered_to)) {
                $defaults['recipient_name'] = $transaction->delivered_to;
            }
        }

        $recentSales = Transaction::with('contact')
            ->where('business_id', $businessId)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->orderByDesc('id')
            ->limit(30)
            ->get(['id', 'invoice_no', 'contact_id', 'final_total', 'transaction_date']);

        $existingParcel = null;
        if (! empty($defaults['transaction_id'])) {
            $existingParcel = DeliveryParcel::where('business_id', $businessId)
                ->where('transaction_id', $defaults['transaction_id'])
                ->where('api_status_code', 200)
                ->orderByDesc('id')
                ->first();
        }

        return view('delivery.create', [
            'defaults' => $defaults,
            'transaction' => $transaction,
            'recentSales' => $recentSales,
            'existingParcel' => $existingParcel,
            'fardarConfigured' => $this->fardar->isConfigured(),
        ]);
    }

    public function store(Request $request)
    {
        $this->checkAccess();

        $data = $request->validate([
            'waybill_mode' => 'required|in:new,existing',
            'waybill_id' => 'nullable|string|max:50',
            'transaction_id' => 'nullable|integer',
            'order_id' => 'nullable|string|max:100',
            'parcel_weight' => 'required|string|max:50',
            'parcel_description' => 'required|string|max:1000',
            'recipient_name' => 'required|string|max:191',
            'recipient_contact_1' => 'required|string|max:30',
            'recipient_contact_2' => 'nullable|string|max:30',
            'recipient_address' => 'required|string|max:2000',
            'recipient_city' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'exchange' => 'required|in:0,1',
        ]);

        if ($data['waybill_mode'] === 'existing' && empty($data['waybill_id'])) {
            return back()->withInput()->with('status', [
                'success' => 0,
                'msg' => 'Waybill ID is required for existing waybill mode.',
            ]);
        }

        // Fardar rejects non-numeric order_id with status 202 — keep digits only.
        $orderId = preg_replace('/\D+/', '', (string) ($data['order_id'] ?? ''));
        $data['order_id'] = $orderId !== '' ? $orderId : null;

        $businessId = session('user.business_id');

        if (! empty($data['transaction_id'])) {
            Transaction::where('business_id', $businessId)
                ->where('type', 'sell')
                ->findOrFail($data['transaction_id']);
        }

        $apiPayload = [
            'order_id' => $data['order_id'] ?? '',
            'parcel_weight' => $data['parcel_weight'],
            'parcel_description' => $data['parcel_description'],
            'recipient_name' => $data['recipient_name'],
            'recipient_contact_1' => $data['recipient_contact_1'],
            'recipient_contact_2' => $data['recipient_contact_2'] ?? '',
            'recipient_address' => $data['recipient_address'],
            'recipient_city' => $data['recipient_city'],
            'amount' => (string) $data['amount'],
            'exchange' => (string) $data['exchange'],
        ];

        if ($data['waybill_mode'] === 'existing') {
            $apiPayload['waybill_id'] = $data['waybill_id'];
            $result = $this->fardar->createExistingWaybill($apiPayload);
        } else {
            $result = $this->fardar->createNewWaybill($apiPayload);
        }

        $parcel = DeliveryParcel::create([
            'business_id' => $businessId,
            'transaction_id' => $data['transaction_id'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'waybill_no' => $result['waybill_no'] ?: ($data['waybill_id'] ?? null),
            'waybill_mode' => $data['waybill_mode'],
            'parcel_weight' => $data['parcel_weight'],
            'parcel_description' => $data['parcel_description'],
            'recipient_name' => $data['recipient_name'],
            'recipient_contact_1' => $data['recipient_contact_1'],
            'recipient_contact_2' => $data['recipient_contact_2'] ?? null,
            'recipient_address' => $data['recipient_address'],
            'recipient_city' => $data['recipient_city'],
            'amount' => $data['amount'],
            'exchange' => (int) $data['exchange'],
            'current_status' => $result['success'] ? 'pending' : 'api_failed',
            'api_status_code' => $result['status'],
            'api_response' => $result['raw'],
            'created_by' => auth()->id(),
        ]);

        if ($result['success'] && $parcel->transaction_id) {
            $tx = Transaction::find($parcel->transaction_id);
            if ($tx) {
                $details = trim((string) $tx->shipping_details);
                $line = 'Fardar Waybill: '.$parcel->waybill_no;
                if ($details === '' || ! str_contains($details, $line)) {
                    $details = $details === '' ? $line : $details."\n".$line;
                }
                $tx->shipping_status = 'shipped';
                $tx->shipping_details = $details;
                $tx->save();
            }
        }

        if (! $result['success']) {
            return redirect()
                ->route('delivery.show', $parcel->id)
                ->with('status', [
                    'success' => 0,
                    'msg' => $result['message'],
                ]);
        }

        return redirect()
            ->route('delivery.show', $parcel->id)
            ->with('status', [
                'success' => 1,
                'msg' => 'Waybill '.$parcel->waybill_no.' created successfully.',
            ]);
    }

    public function show($id)
    {
        $this->checkAccess();

        $parcel = DeliveryParcel::with(['transaction.contact', 'creator'])
            ->where('business_id', session('user.business_id'))
            ->findOrFail($id);

        return view('delivery.show', [
            'parcel' => $parcel,
            'fardar' => $this->fardar,
        ]);
    }

    public function searchSales(Request $request)
    {
        $this->checkAccess();

        $q = trim((string) $request->get('q', ''));
        $businessId = session('user.business_id');

        $sales = Transaction::with('contact')
            ->where('business_id', $businessId)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('invoice_no', 'like', "%{$q}%")
                        ->orWhere('id', $q)
                        ->orWhereHas('contact', function ($c) use ($q) {
                            $c->where('name', 'like', "%{$q}%")
                                ->orWhere('mobile', 'like', "%{$q}%");
                        });
                });
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(function (Transaction $t) {
                return [
                    'id' => $t->id,
                    'invoice_no' => $t->invoice_no,
                    'customer' => optional($t->contact)->name,
                    'mobile' => optional($t->contact)->mobile,
                    'total' => $t->final_total,
                    'date' => optional($t->transaction_date)->format('Y-m-d'),
                ];
            });

        return response()->json(['data' => $sales]);
    }

    /**
     * Fardar Reverse API callback — updates parcel tracking status.
     */
    public function statusWebhook(Request $request)
    {
        $waybillId = $request->input('waybill_id')
            ?? $request->input('waybill_no');
        $deliveryStatus = $request->input('delivery_status')
            ?? $request->input('current_status');
        $lastUpdate = $request->input('last_update_time');

        if (empty($waybillId) || empty($deliveryStatus)) {
            Log::warning('Fardar webhook missing fields', $request->all());

            return response()->json(['status' => 400, 'message' => 'Missing waybill_id or delivery_status'], 400);
        }

        $parcel = DeliveryParcel::where('waybill_no', $waybillId)->first();

        if (! $parcel) {
            Log::info('Fardar webhook for unknown waybill', [
                'waybill_id' => $waybillId,
                'status' => $deliveryStatus,
            ]);

            return response()->json(['status' => 404, 'message' => 'Waybill not found'], 404);
        }

        $parcel->current_status = $deliveryStatus;
        $parcel->last_update_time = $lastUpdate
            ? date('Y-m-d H:i:s', strtotime($lastUpdate))
            : now();
        $parcel->save();

        if ($parcel->transaction_id) {
            $shippingStatus = 'shipped';
            $lower = strtolower($deliveryStatus);
            if (str_contains($lower, 'deliver') || str_contains($lower, 'complet')) {
                $shippingStatus = 'delivered';
            } elseif (str_contains($lower, 'cancel')) {
                $shippingStatus = 'cancelled';
            } elseif (str_contains($lower, 'pack')) {
                $shippingStatus = 'packed';
            }

            Transaction::where('id', $parcel->transaction_id)->update([
                'shipping_status' => $shippingStatus,
            ]);
        }

        return response()->json(['status' => 200, 'message' => 'Updated']);
    }

    protected function buildAddress(?Contact $contact, ?Transaction $transaction): string
    {
        $parts = [];

        if ($transaction && ! empty($transaction->shipping_address)) {
            return trim($transaction->shipping_address);
        }

        if (! $contact) {
            return '';
        }

        foreach (['address_line_1', 'address_line_2', 'city', 'state', 'zip_code', 'country'] as $field) {
            if (! empty($contact->{$field})) {
                $parts[] = $contact->{$field};
            }
        }

        if (empty($parts) && ! empty($contact->shipping_address)) {
            return trim($contact->shipping_address);
        }

        return implode(', ', $parts);
    }
}
