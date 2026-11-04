<?php

namespace App\Http\Controllers;

use App\Contact;
use App\InventoryCategory;
use App\InventoryMaterial;
use App\InventoryPurchase;
use App\InventoryPurchaseLine;
use App\InventoryPurchasePayment;
use App\InventoryUnit;
use App\TaxRate;
use App\Utils\ModuleUtil;
use App\Utils\RawMaterialAccounting;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            || $this->hasAccountingAccess()
        ) {
            return;
        }

        abort(403, 'Unauthorized.');
    }

    private function hasAccountingAccess(): bool
    {
        $user = auth()->user();

        return $user->can('accounting.map_transactions')
            || $user->can('accounting.manage_accounts')
            || $user->can('accounting.chart_of_accounts.index')
            || $user->can('accounting.journal_entries.index');
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
        RawMaterialAccounting::backfill((int) $businessId);

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

        $accounts = app(ModuleUtil::class)->accountsDropdown($businessId, true);

        return view('inventory.purchases.index', compact('suppliers', 'accounts'));
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
        $taxes = TaxRate::where('business_id', $businessId)->get();
        $accounts = app(ModuleUtil::class)->accountsDropdown($businessId, true);
        $supplierBalances = Contact::where('business_id', $businessId)
            ->whereIn('type', ['supplier', 'both'])
            ->pluck('balance', 'id');

        return view('inventory.purchases.create', compact(
            'suppliers', 'materials', 'materialsJson', 'categories', 'units', 'taxes', 'accounts', 'supplierBalances'
        ));
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

        $this->ensurePurchaseExtraColumns();

        $data = $request->validate([
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'purchase_date' => ['required', 'date'],
            'pay_term_number' => ['nullable', 'integer', 'min:0'],
            'pay_term_type' => ['nullable', 'in:days,months'],
            'status' => ['required', 'in:ordered,received'],
            'ref_no' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'discount_type' => ['nullable', 'in:fixed,percentage'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_id' => ['nullable', 'integer', 'exists:tax_rates,id'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'paid_on' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'in:cash,card,cheque,bank,advance'],
            'account_id' => ['nullable', 'integer'],
            'cheque_number' => ['nullable', 'string', 'max:60'],
            'cheque_date' => ['nullable', 'date'],
            'reference_no' => ['nullable', 'string', 'max:80'],
            'payment_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'accounting_deposit_to' => ['nullable', 'integer'],
            'accounting_payment_account' => ['nullable', 'integer'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.material_id' => ['required', 'integer', 'exists:inventory_materials,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $businessId = $request->session()->get('user.business_id');
        $paidNowCheck = round((float) ($data['amount_paid'] ?? 0), 4);
        $paymentMethod = $data['payment_method'] ?? 'cash';
        $paymentFile = null;
        if ($paidNowCheck > 0) {
            $this->assertPaymentDetails(
                $paymentMethod,
                $data['cheque_number'] ?? null,
                $data['reference_no'] ?? null,
                $data['cheque_date'] ?? null
            );
            if ($paymentMethod === 'bank') {
                $paymentFile = $this->storePaymentFile($request);
            }
            if ($paymentMethod === 'advance') {
                $balance = (float) (Contact::where('id', $data['contact_id'] ?? 0)->value('balance') ?? 0);
                if ($paidNowCheck > $balance + 0.009) {
                    throw ValidationException::withMessages([
                        'amount_paid' => 'Advance balance is only Rs. '.number_format($balance, 2).'.',
                    ]);
                }
            }
        }

        $linkedAccounts = [
            'deposit_to' => $data['accounting_deposit_to'] ?? null,
            'payment_account' => $data['accounting_payment_account'] ?? null,
        ];

        $purchase = DB::transaction(function () use ($data, $businessId, $paymentMethod, $paymentFile, $linkedAccounts) {
            $subtotal = 0;
            foreach ($data['lines'] as $line) {
                $subtotal += ((float) $line['quantity']) * ((float) $line['unit_cost']);
            }
            $subtotal = round($subtotal, 4);

            $discountType = $data['discount_type'] ?? null;
            $discountAmount = round((float) ($data['discount_amount'] ?? 0), 4);
            $discount = 0.0;
            if ($discountAmount > 0) {
                if ($discountType === 'percentage') {
                    $discount = round($subtotal * ($discountAmount / 100), 4);
                } else {
                    $discountType = 'fixed';
                    $discount = min($discountAmount, $subtotal);
                }
            }
            $totalBeforeTax = round(max(0, $subtotal - $discount), 4);

            $taxAmount = 0.0;
            $taxId = $data['tax_id'] ?? null;
            if (! empty($taxId)) {
                $tax = TaxRate::where('business_id', $businessId)->where('id', $taxId)->first();
                if ($tax) {
                    $taxAmount = round($totalBeforeTax * ((float) $tax->amount / 100), 4);
                } else {
                    $taxId = null;
                }
            }
            $total = round($totalBeforeTax + $taxAmount, 4);

            $ref = trim((string) ($data['ref_no'] ?? ''));
            if ($ref === '') {
                $count = InventoryPurchase::where('business_id', $businessId)->count() + 1;
                $ref = 'RMP-'.date('Y').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
            }

            $payload = [
                'business_id' => $businessId,
                'ref_no' => $ref,
                'contact_id' => $data['contact_id'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'status' => $data['status'],
                'total_amount' => $total,
                'payment_status' => 'due',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ];
            if (Schema::hasColumn('inventory_purchases', 'pay_term_number')) {
                $payload['pay_term_number'] = $data['pay_term_number'] ?? null;
                $payload['pay_term_type'] = ! empty($data['pay_term_number']) ? ($data['pay_term_type'] ?? 'days') : null;
            }
            if (Schema::hasColumn('inventory_purchases', 'discount_type')) {
                $payload['discount_type'] = $discount > 0 ? $discountType : null;
                $payload['discount_amount'] = $discountAmount;
                $payload['tax_id'] = $taxId;
                $payload['tax_amount'] = $taxAmount;
                $payload['total_before_tax'] = $totalBeforeTax;
            }

            $purchase = InventoryPurchase::create($payload);

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
            RawMaterialAccounting::syncPurchase($purchase, $linkedAccounts);
            if ($paidNow > 0) {
                $paidOn = $data['paid_on'] ?? $data['purchase_date'];
                $this->recordPayment($purchase, $paidNow, $paidOn, $paymentMethod, 'Paid when purchase was saved', [
                    'cheque_number' => $paymentMethod === 'cheque' ? ($data['cheque_number'] ?? null) : null,
                    'cheque_date' => $paymentMethod === 'cheque' ? ($data['cheque_date'] ?? null) : null,
                    'reference_no' => $paymentMethod === 'bank' ? ($data['reference_no'] ?? null) : null,
                    'attachment' => $paymentFile,
                    'account_id' => $paymentMethod === 'advance' ? null : ($data['account_id'] ?? null),
                ]);
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

        $this->ensurePurchaseExtraColumns();
        $purchase->load(['supplier', 'creator', 'lines.material.unit', 'payments', 'tax']);
        $accounts = app(ModuleUtil::class)->accountsDropdown($businessId, true);
        $advanceBalance = (float) ($purchase->supplier->balance ?? 0);

        return view('inventory.purchases.show', compact('purchase', 'accounts', 'advanceBalance'));
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
                    'cheque_number' => $payment->cheque_number,
                    'cheque_date' => $payment->cheque_date ? $payment->cheque_date->format('d M Y') : '',
                    'reference_no' => $payment->reference_no,
                    'attachment' => $payment->attachment ? asset('uploads/raw-material-payments/'.$payment->attachment) : null,
                    'amount' => number_format((float) $payment->amount, 2),
                    'note' => $payment->note,
                ];
            })->values(),
        ]);
    }

    public function dueCheques()
    {
        $this->checkAccess();
        $this->ensurePaymentDetailColumns();

        $businessId = (int) request()->session()->get('user.business_id');
        $today = now()->toDateString();
        $filter = request()->get('filter', 'due'); // due | upcoming | all

        $query = InventoryPurchasePayment::query()
            ->join('inventory_purchases', 'inventory_purchases.id', '=', 'inventory_purchase_payments.inventory_purchase_id')
            ->leftJoin('contacts', 'contacts.id', '=', 'inventory_purchases.contact_id')
            ->where('inventory_purchases.business_id', $businessId)
            ->where('inventory_purchase_payments.method', 'cheque')
            ->whereNotNull('inventory_purchase_payments.cheque_date')
            ->select([
                'inventory_purchase_payments.id',
                'inventory_purchase_payments.amount',
                'inventory_purchase_payments.cheque_number',
                'inventory_purchase_payments.cheque_date',
                'inventory_purchase_payments.paid_on',
                'inventory_purchases.id as purchase_id',
                'inventory_purchases.ref_no',
                'inventory_purchases.purchase_date',
                DB::raw("COALESCE(contacts.supplier_business_name, contacts.name, '—') as supplier_name"),
            ])
            ->orderBy('inventory_purchase_payments.cheque_date')
            ->orderBy('inventory_purchase_payments.id');

        if ($filter === 'due') {
            $query->whereDate('inventory_purchase_payments.cheque_date', '<=', $today);
        } elseif ($filter === 'upcoming') {
            $query->whereDate('inventory_purchase_payments.cheque_date', '>', $today);
        }

        $cheques = $query->get();
        $dueCount = InventoryPurchasePayment::query()
            ->join('inventory_purchases', 'inventory_purchases.id', '=', 'inventory_purchase_payments.inventory_purchase_id')
            ->where('inventory_purchases.business_id', $businessId)
            ->where('inventory_purchase_payments.method', 'cheque')
            ->whereNotNull('inventory_purchase_payments.cheque_date')
            ->whereDate('inventory_purchase_payments.cheque_date', '<=', $today)
            ->count();

        return view('inventory.purchases.due_cheques', compact('cheques', 'filter', 'dueCount', 'today'));
    }

    public function storePayment(Request $request, InventoryPurchase $purchase)
    {
        $this->checkAccess();
        if (! $this->canCreate()) {
            abort(403);
        }
        $this->assertPurchaseBusiness($purchase);

        $this->ensurePurchaseExtraColumns();

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_on' => ['required', 'date'],
            'method' => ['required', 'in:cash,card,cheque,bank,advance'],
            'account_id' => ['nullable', 'integer'],
            'cheque_number' => ['nullable', 'string', 'max:60'],
            'cheque_date' => ['nullable', 'date'],
            'reference_no' => ['nullable', 'string', 'max:80'],
            'payment_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);
        $this->assertPaymentDetails(
            $data['method'],
            $data['cheque_number'] ?? null,
            $data['reference_no'] ?? null,
            $data['cheque_date'] ?? null
        );

        $due = $purchase->amountDue();
        if ((float) $data['amount'] > $due + 0.009) {
            throw ValidationException::withMessages([
                'amount' => 'Payment cannot be more than the amount still due (Rs. '.number_format($due, 2).').',
            ]);
        }

        if ($data['method'] === 'advance') {
            $balance = (float) (Contact::where('id', $purchase->contact_id)->value('balance') ?? 0);
            if ((float) $data['amount'] > $balance + 0.009) {
                throw ValidationException::withMessages([
                    'amount' => 'Advance balance is only Rs. '.number_format($balance, 2).'.',
                ]);
            }
        }

        $this->recordPayment($purchase, (float) $data['amount'], $data['paid_on'], $data['method'], $data['note'] ?? null, [
            'cheque_number' => $data['method'] === 'cheque' ? ($data['cheque_number'] ?? null) : null,
            'cheque_date' => $data['method'] === 'cheque' ? ($data['cheque_date'] ?? null) : null,
            'reference_no' => $data['method'] === 'bank' ? ($data['reference_no'] ?? null) : null,
            'attachment' => $data['method'] === 'bank' ? $this->storePaymentFile($request) : null,
            'account_id' => $data['method'] === 'advance' ? null : ($data['account_id'] ?? null),
        ]);

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
            RawMaterialAccounting::syncPurchase($purchase->fresh());
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

    private function assertPaymentDetails(string $method, ?string $chequeNumber, ?string $referenceNo, ?string $chequeDate = null): void
    {
        if ($method === 'cheque') {
            $errors = [];
            if (trim((string) $chequeNumber) === '') {
                $errors['cheque_number'] = 'Enter the cheque number.';
            }
            if (trim((string) $chequeDate) === '') {
                $errors['cheque_date'] = 'Enter the cheque date.';
            }
            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }
        }
        if ($method === 'bank' && trim((string) $referenceNo) === '') {
            throw ValidationException::withMessages([
                'reference_no' => 'Enter the transfer id or reference number.',
            ]);
        }
    }

    private function storePaymentFile(Request $request): ?string
    {
        if (! $request->hasFile('payment_attachment')) {
            return null;
        }

        $file = $request->file('payment_attachment');
        $dir = public_path('uploads/raw-material-payments');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = time().'_'.preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
        $file->move($dir, $name);

        return $name;
    }

    private function recordPayment(InventoryPurchase $purchase, float $amount, $paidOn, string $method, ?string $note, array $extra = []): void
    {
        $this->ensurePaymentDetailColumns();

        $payload = [
            'inventory_purchase_id' => $purchase->id,
            'amount' => round($amount, 4),
            'paid_on' => $paidOn,
            'method' => $method,
            'note' => $note,
            'created_by' => auth()->id(),
        ];

        if (Schema::hasColumn('inventory_purchase_payments', 'cheque_number')) {
            $payload['cheque_number'] = $extra['cheque_number'] ?? null;
        }
        if (Schema::hasColumn('inventory_purchase_payments', 'cheque_date')) {
            $payload['cheque_date'] = $extra['cheque_date'] ?? null;
        }
        if (Schema::hasColumn('inventory_purchase_payments', 'reference_no')) {
            $payload['reference_no'] = $extra['reference_no'] ?? null;
        }
        if (Schema::hasColumn('inventory_purchase_payments', 'attachment')) {
            $payload['attachment'] = $extra['attachment'] ?? null;
        }
        if (Schema::hasColumn('inventory_purchase_payments', 'account_id')) {
            $payload['account_id'] = $extra['account_id'] ?? null;
        }

        $payment = InventoryPurchasePayment::create($payload);

        if ($method === 'advance' && ! empty($purchase->contact_id)) {
            Contact::where('id', $purchase->contact_id)->decrement('balance', round($amount, 4));
        }

        $purchase->refreshPaymentStatus();
        RawMaterialAccounting::syncPayment($purchase->fresh(), $payment);
    }

    /**
     * Production may not have run the cheque/bank payment migration yet.
     */
    private function ensurePaymentDetailColumns(): void
    {
        if (! Schema::hasTable('inventory_purchase_payments')) {
            return;
        }

        if (! Schema::hasColumn('inventory_purchase_payments', 'cheque_number')) {
            Schema::table('inventory_purchase_payments', function (Blueprint $table) {
                $table->string('cheque_number', 60)->nullable()->after('method');
            });
        }
        if (! Schema::hasColumn('inventory_purchase_payments', 'cheque_date')) {
            Schema::table('inventory_purchase_payments', function (Blueprint $table) {
                $table->date('cheque_date')->nullable()->after('cheque_number');
            });
        }
        if (! Schema::hasColumn('inventory_purchase_payments', 'reference_no')) {
            Schema::table('inventory_purchase_payments', function (Blueprint $table) {
                $after = Schema::hasColumn('inventory_purchase_payments', 'cheque_date') ? 'cheque_date' : 'cheque_number';
                $table->string('reference_no', 80)->nullable()->after($after);
            });
        }
        if (! Schema::hasColumn('inventory_purchase_payments', 'attachment')) {
            Schema::table('inventory_purchase_payments', function (Blueprint $table) {
                $table->string('attachment')->nullable()->after('reference_no');
            });
        }
        if (! Schema::hasColumn('inventory_purchase_payments', 'account_id')) {
            Schema::table('inventory_purchase_payments', function (Blueprint $table) {
                $table->unsignedInteger('account_id')->nullable()->after('method');
            });
        }
    }

    private function ensurePurchaseExtraColumns(): void
    {
        $this->ensurePaymentDetailColumns();
        if (! Schema::hasTable('inventory_purchases')) {
            return;
        }
        if (! Schema::hasColumn('inventory_purchases', 'pay_term_number')) {
            Schema::table('inventory_purchases', function (Blueprint $table) {
                $table->integer('pay_term_number')->nullable()->after('purchase_date');
                $table->string('pay_term_type', 20)->nullable()->after('pay_term_number');
            });
        }
        if (! Schema::hasColumn('inventory_purchases', 'discount_type')) {
            Schema::table('inventory_purchases', function (Blueprint $table) {
                $table->string('discount_type', 20)->nullable()->after('status');
                $table->decimal('discount_amount', 22, 4)->default(0)->after('discount_type');
                $table->unsignedInteger('tax_id')->nullable()->after('discount_amount');
                $table->decimal('tax_amount', 22, 4)->default(0)->after('tax_id');
                $table->decimal('total_before_tax', 22, 4)->nullable()->after('tax_amount');
            });
        }
    }

    public function duePurchases()
    {
        $this->checkAccess();
        $this->ensurePurchaseExtraColumns();

        $businessId = (int) request()->session()->get('user.business_id');
        $filter = request()->get('filter', 'due'); // due | overdue | upcoming | all
        $today = Carbon::today();

        $purchases = InventoryPurchase::query()
            ->with('supplier')
            ->where('business_id', $businessId)
            ->whereIn('payment_status', ['due', 'partial'])
            ->whereNotNull('pay_term_number')
            ->whereNotNull('pay_term_type')
            ->orderByDesc('purchase_date')
            ->get()
            ->map(function ($purchase) use ($today) {
                $dueDate = $purchase->dueDate();
                $purchase->computed_due_date = $dueDate;
                $purchase->days_to_due = $dueDate ? $today->diffInDays($dueDate, false) : null;
                $purchase->computed_overdue = $dueDate ? $dueDate->lt($today) : false;
                $purchase->remaining_due = $purchase->amountDue();

                return $purchase;
            })
            ->filter(function ($purchase) use ($filter) {
                if ($purchase->remaining_due <= 0.009) {
                    return false;
                }
                if ($filter === 'overdue') {
                    return $purchase->computed_overdue;
                }
                if ($filter === 'upcoming') {
                    return ! $purchase->computed_overdue && $purchase->days_to_due !== null && $purchase->days_to_due <= 7;
                }
                if ($filter === 'due') {
                    return $purchase->computed_overdue || ($purchase->days_to_due !== null && $purchase->days_to_due <= 7);
                }

                return true;
            })
            ->values();

        $bySupplier = $purchases->groupBy(function ($p) {
            return $p->supplier?->supplier_business_name ?: ($p->supplier?->name ?: 'No supplier');
        })->map(function ($rows) {
            return [
                'count' => $rows->count(),
                'due' => round($rows->sum('remaining_due'), 2),
                'overdue' => $rows->where('computed_overdue', true)->count(),
            ];
        });

        return view('inventory.purchases.due', compact('purchases', 'filter', 'bySupplier', 'today'));
    }

    public function supplierStatement(Request $request)
    {
        $this->checkAccess();
        $this->ensurePurchaseExtraColumns();

        $businessId = (int) $request->session()->get('user.business_id');
        $suppliers = Contact::suppliersDropdown($businessId, false);
        $supplierId = (int) $request->get('supplier_id');
        $supplier = null;
        $purchases = collect();
        $payments = collect();
        $advanceBalance = 0.0;
        $purchaseTotal = 0.0;
        $paymentTotal = 0.0;
        $purchaseDue = 0.0;

        if ($supplierId > 0) {
            $supplier = Contact::where('business_id', $businessId)->where('id', $supplierId)->firstOrFail();
            $advanceBalance = (float) ($supplier->balance ?? 0);
            $purchases = InventoryPurchase::with('payments')
                ->where('business_id', $businessId)
                ->where('contact_id', $supplierId)
                ->orderBy('purchase_date')
                ->orderBy('id')
                ->get();
            foreach ($purchases as $purchase) {
                $purchaseTotal += (float) $purchase->total_amount;
                $purchaseDue += $purchase->amountDue();
                foreach ($purchase->payments as $payment) {
                    $payments->push([
                        'date' => $payment->paid_on,
                        'ref' => $purchase->ref_no,
                        'method' => $payment->method,
                        'amount' => (float) $payment->amount,
                        'note' => $payment->note,
                        'purchase_id' => $purchase->id,
                    ]);
                    $paymentTotal += (float) $payment->amount;
                }
            }
            $payments = $payments->sortBy('date')->values();
        }

        return view('inventory.purchases.supplier_statement', compact(
            'suppliers', 'supplierId', 'supplier', 'purchases', 'payments',
            'advanceBalance', 'purchaseTotal', 'paymentTotal', 'purchaseDue'
        ));
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
