<?php

namespace App\Http\Controllers;

use App\Contact;
use App\InventoryMaterial;
use App\InventoryPurchase;
use App\InventoryPurchaseLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $purchases = InventoryPurchase::with(['supplier', 'creator', 'lines.material.unit'])
            ->where('business_id', $businessId)
            ->orderByDesc('id')
            ->paginate(25);

        return view('inventory.purchases.index', compact('purchases'));
    }

    public function create()
    {
        $this->checkAccess();
        if (! $this->canCreate()) {
            abort(403);
        }

        $businessId = request()->session()->get('user.business_id');
        $suppliers = Contact::suppliersDropdown($businessId, false);
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

        return view('inventory.purchases.create', compact('suppliers', 'materials', 'materialsJson'));
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

        $purchase->load(['supplier', 'creator', 'lines.material.unit']);

        return view('inventory.purchases.show', compact('purchase'));
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
