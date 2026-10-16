<?php

namespace App\Http\Controllers;

use App\Contact;
use App\InventoryCategory;
use App\InventoryMaterial;
use App\InventoryPurchase;
use App\InventoryPurchaseLine;
use App\InventoryPurchasePayment;
use App\InventoryUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class InventoryPurchaseController extends Controller
{
    private function checkAccess(): void
    {
        if (
            auth()->user()->can('purchase.create')
            || auth()->user()->can('purchase.view')
            || auth()->user()->can('send_notifications')
            || auth()->user()->can('production.access')
        ) {
            return;
        }

        abort(403, 'Unauthorized.');
    }

    private function canCreate(): bool
    {
        return auth()->user()->can('purchase.create')
            || auth()->user()->can('send_notifications')
            || auth()->user()->can('production.access');
    }

    public function index()
    {
        $this->checkAccess();

        $businessId = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $paidSql = '(SELECT COALESCE(SUM(amount),0) FROM inventory_purchase_payments WHERE inventory_purchase_id = inventory_purchases.id)';
            $materialsSql = "(SELECT GROUP_CONCAT(im.name ORDER BY im.name SEPARATOR ', ') FROM inventory_purchase_lines ipl INNER JOIN inventory_materials im ON im.id = ipl.material_id WHERE ipl.inventory_purchase_id = inventory_purchases.id)";

            $purchases = InventoryPurchase::query()
                ->leftJoin('contacts', 'contacts.id', '=', 'inventory_purchases.contact_id')
                ->where('inventory_purchases.business_id', $businessId)
                ->select([
                    'inventory_purchases.id',
                    'inventory_purchases.ref_no',
                    'inventory_purchases.purchase_date',
                    'inventory_purchases.status',
                    'inventory_purchases.payment_status',
                    'inventory_purchases.total_amount',
                    DB::raw("COALESCE(contacts.supplier_business_name, contacts.name, '—') as supplier_name"),
                    DB::raw($paidSql.' as amount_paid'),
                    DB::raw($materialsSql.' as material_names'),
                ]);

            if (! empty(request()->supplier_id)) {
                $purchases->where('inventory_purchases.contact_id', request()->supplier_id);
            }
            if (! empty(request()->status)) {
                $purchases->where('inventory_purchases.status', request()->status);
            }
            if (! empty(request()->payment_status)) {
                $purchases->where('inventory_purchases.payment_status', request()->payment_status);
            }
            if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                $purchases->whereDate('inventory_purchases.purchase_date', '>=', request()->start_date)
                    ->whereDate('inventory_purchases.purchase_date', '<=', request()->end_date);
            }

            $keyword = request()->input('search.value');
            if ($keyword !== null && $keyword !== '') {
                $like = '%'.$keyword.'%';
                $purchases->where(function ($q) use ($like) {
                    $q->where('inventory_purchases.ref_no', 'like', $like)
                        ->orWhere('contacts.name', 'like', $like)
                        ->orWhere('contacts.supplier_business_name', 'like', $like)
                        ->orWhereRaw('exists (select 1 from inventory_purchase_lines ipl inner join inventory_materials im on im.id = ipl.material_id where ipl.inventory_purchase_id = inventory_purchases.id and im.name like ?)', [$like]);
                });
            }

            return DataTables::of($purchases)
                ->filter(function () {
                }, false)
                ->editColumn('purchase_date', function ($row) {
                    return $row->purchase_date ? \Carbon\Carbon::parse($row->purchase_date)->format('d M Y') : '';
                })
                ->editColumn('material_names', function ($row) {
                    return e($row->material_names ?: '—');
                })
                ->editColumn('status', function ($row) {
                    return '<span class="rmp-badge '.$row->status.'">'.e($row->status).'</span>';
                })
                ->editColumn('payment_status', function ($row) {
                    $label = $row->payment_status ?: 'due';

                    return '<span class="rmp-badge pay-'.$label.'">'.e($label).'</span>';
                })
                ->editColumn('total_amount', function ($row) {
                    return 'Rs. '.number_format((float) $row->total_amount, 2);
                })
                ->addColumn('paid', function ($row) {
                    return 'Rs. '.number_format((float) $row->amount_paid, 2);
                })
                ->addColumn('due', function ($row) {
                    $due = max(0, (float) $row->total_amount - (float) $row->amount_paid);

                    return 'Rs. '.number_format($due, 2);
                })
                ->addColumn('action', function ($row) {
                    $due = max(0, (float) $row->total_amount - (float) $row->amount_paid);
                    $html = '<a href="'.route('inventory.purchases.show', $row->id).'">View</a>';
                    if ($due > 0.009) {
                        $html .= ' · <a href="#" class="rmp-add-pay" data-id="'.$row->id.'" data-ref="'.e($row->ref_no).'" data-due="'.number_format($due, 2, '.', '').'">Add payment</a>';
                    }
                    $html .= ' · <a href="#" class="rmp-view-pay" data-id="'.$row->id.'" data-ref="'.e($row->ref_no).'">Payments</a>';

                    return $html;
                })
                ->rawColumns(['status', 'payment_status', 'action'])
                ->make(true);
        }

        $suppliers = Contact::suppliersDropdown($businessId, false);

        return view('inventory.purchases.index', compact('suppliers'));
    }

    public function create()
    {
        $this->checkAccess();
        if (! $this->canCreate()) {
            abort(403);
        }

        $businessId = request()->session()->get('user.business_id');
        $suppliers = Contact::suppliersDropdown($businessId, false);
        $categories = InventoryCategory::orderBy('name')->get();
        $units = InventoryUnit::orderBy('name')->get();
        $materials = InventoryMaterial::with('unit')->orderBy('name')->get();
        $materialsJson = $materials->map(function ($m) {
            return [
                'id' => $m->id,
                'name' => $m->name,
                'sku' => $m->sku,
                'unit' => optional($m->unit)->abbreviation ?: '',
                'price' => (float) $m->price_per_unit,
                'stock' => (float) $m->current_stock,
            ];
        })->values();

        return view('inventory.purchases.create', compact('suppliers', 'materials', 'materialsJson', 'categories', 'units'));
    }

    public function storeMaterial(Request $request)
    {
        $this->checkAccess();
        if (! $this->canCreate()) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:40', 'unique:inventory_materials,sku'],
            'category_id' => ['nullable', 'integer', 'exists:inventory_categories,id'],
            'unit_id' => ['required', 'integer', 'exists:inventory_units,id'],
            'price_per_unit' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $data['reorder_level'] = $data['reorder_level'] ?? 0;
        $data['current_stock'] = 0;
        if (empty($data['sku'])) {
            unset($data['sku']);
        }

        $material = InventoryMaterial::create($data);
        $material->load('unit');

        return response()->json([
            'success' => true,
            'material' => [
                'id' => $material->id,
                'name' => $material->name,
                'sku' => $material->sku,
                'unit' => optional($material->unit)->abbreviation ?: '',
                'price' => (float) $material->price_per_unit,
                'stock' => (float) $material->current_stock,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->checkAccess();
        if (! $this->canCreate()) {
            abort(403);
        }

        $data = $request->validate([
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'purchase_date' => ['required', 'date'],
            'status' => ['required', 'in:ordered,received'],
            'ref_no' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:30'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.material_id' => ['required', 'integer', 'exists:inventory_materials,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $businessId = $request->session()->get('user.business_id');

        $purchase = DB::transaction(function () use ($data, $businessId) {
            $total = 0;
            foreach ($data['lines'] as $line) {
                $total += ((float) $line['quantity']) * ((float) $line['unit_cost']);
            }

            $ref = trim((string) ($data['ref_no'] ?? ''));
            if ($ref === '') {
                $count = InventoryPurchase::where('business_id', $businessId)->count() + 1;
                $ref = 'RMP-'.date('Y').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
            }

            $purchase = InventoryPurchase::create([
                'business_id' => $businessId,
                'ref_no' => $ref,
                'contact_id' => $data['contact_id'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'status' => $data['status'],
                'total_amount' => $total,
                'payment_status' => 'due',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($data['lines'] as $line) {
                $qty = (float) $line['quantity'];
                $cost = (float) $line['unit_cost'];
                $lineTotal = $qty * $cost;

                InventoryPurchaseLine::create([
                    'inventory_purchase_id' => $purchase->id,
                    'material_id' => (int) $line['material_id'],
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'line_total' => $lineTotal,
                ]);

                if ($data['status'] === 'received') {
                    $this->applyStock((int) $line['material_id'], $qty, $cost);
                }
            }

            $paidNow = round((float) ($data['amount_paid'] ?? 0), 4);
            if ($paidNow > $total + 0.009) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Paid amount cannot be more than the purchase total.',
                ]);
            }
            if ($paidNow > 0) {
                $this->recordPayment($purchase, $paidNow, $data['purchase_date'], $data['payment_method'] ?? 'cash', 'Paid when purchase was saved');
            }

            return $purchase;
        });

        return redirect()
            ->route('inventory.purchases.show', $purchase)
            ->with('success', 'Raw material purchase saved. Stock updated in Raw Materials.');
    }

    public function show(InventoryPurchase $purchase)
    {
        $this->checkAccess();

        $businessId = request()->session()->get('user.business_id');
        if ((int) $purchase->business_id !== (int) $businessId) {
            abort(404);
        }

        $purchase->load(['supplier', 'creator', 'lines.material.unit', 'payments']);

        return view('inventory.purchases.show', compact('purchase'));
    }

    public function payments(InventoryPurchase $purchase)
    {
        $this->checkAccess();
        $this->assertPurchaseBusiness($purchase);

        $purchase->load('payments');

        return response()->json([
            'ref_no' => $purchase->ref_no,
            'total' => round((float) $purchase->total_amount, 2),
            'paid' => round($purchase->amountPaid(), 2),
            'due' => round($purchase->amountDue(), 2),
            'payments' => $purchase->payments->map(function ($payment) {
                return [
                    'paid_on' => $payment->paid_on ? $payment->paid_on->format('d M Y') : '',
                    'method' => $payment->method,
                    'amount' => number_format((float) $payment->amount, 2),
                    'note' => $payment->note,
                ];
            })->values(),
        ]);
    }

    public function storePayment(Request $request, InventoryPurchase $purchase)
    {
        $this->checkAccess();
        if (! $this->canCreate()) {
            abort(403);
        }
        $this->assertPurchaseBusiness($purchase);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_on' => ['required', 'date'],
            'method' => ['required', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $due = $purchase->amountDue();
        if ((float) $data['amount'] > $due + 0.009) {
            throw ValidationException::withMessages([
                'amount' => 'Payment cannot be more than the amount still due (Rs. '.number_format($due, 2).').',
            ]);
        }

        $this->recordPayment($purchase, (float) $data['amount'], $data['paid_on'], $data['method'], $data['note'] ?? null);

        if ($request->expectsJson()) {
            $purchase->refresh();

            return response()->json([
                'success' => true,
                'paid' => round($purchase->amountPaid(), 2),
                'due' => round($purchase->amountDue(), 2),
                'payment_status' => $purchase->payment_status,
            ]);
        }

        return back()->with('success', 'Payment saved.');
    }

    public function markReceived(InventoryPurchase $purchase)
    {
        $this->checkAccess();
        if (! $this->canCreate()) {
            abort(403);
        }

        $businessId = request()->session()->get('user.business_id');
        if ((int) $purchase->business_id !== (int) $businessId) {
            abort(404);
        }

        if ($purchase->status === 'received') {
            return back()->with('success', 'Already received.');
        }

        DB::transaction(function () use ($purchase) {
            $purchase->load('lines');
            foreach ($purchase->lines as $line) {
                $this->applyStock((int) $line->material_id, (float) $line->quantity, (float) $line->unit_cost);
            }
            $purchase->update(['status' => 'received']);
        });

        return back()->with('success', 'Marked as received — raw material stock updated.');
    }

    private function assertPurchaseBusiness(InventoryPurchase $purchase): void
    {
        $businessId = request()->session()->get('user.business_id');
        if ((int) $purchase->business_id !== (int) $businessId) {
            abort(404);
        }
    }

    private function recordPayment(InventoryPurchase $purchase, float $amount, $paidOn, string $method, ?string $note): void
    {
        InventoryPurchasePayment::create([
            'inventory_purchase_id' => $purchase->id,
            'amount' => round($amount, 4),
            'paid_on' => $paidOn,
            'method' => $method,
            'note' => $note,
            'created_by' => auth()->id(),
        ]);
        $purchase->refreshPaymentStatus();
    }

    /**
     * Increase stock and set weighted-average purchase cost (no sell price).
     */
    private function applyStock(int $materialId, float $qty, float $unitCost): void
    {
        $material = InventoryMaterial::lockForUpdate()->findOrFail($materialId);
        $oldStock = (float) $material->current_stock;
        $oldCost = (float) $material->price_per_unit;
        $newStock = $oldStock + $qty;

        $newCost = $newStock > 0
            ? ((($oldStock * $oldCost) + ($qty * $unitCost)) / $newStock)
            : $unitCost;

        $material->update([
            'current_stock' => $newStock,
            'price_per_unit' => round($newCost, 4),
        ]);
    }
}
