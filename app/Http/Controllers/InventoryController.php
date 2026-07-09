<?php

namespace App\Http\Controllers;

use App\InventoryCategory;
use App\InventoryMaterial;
use App\InventoryUnit;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    private function checkAccess(): void
    {
        if (! auth()->user()->can('send_notifications') &&
            ! auth()->user()->can('production.access') &&
            ! \App\ProductionStageEmployee::where('user_id', auth()->id())->exists()) {
            abort(403, 'Unauthorized.');
        }
    }

    private function isAdmin(): bool
    {
        return auth()->user()->can('send_notifications');
    }

    // ── Materials ─────────────────────────────────────────────────────────────

    public function materials(Request $request)
    {
        $this->checkAccess();

        $q          = $request->get('q');
        $categoryId = $request->get('category_id');

        $query = InventoryMaterial::with(['category', 'unit'])->orderBy('name');

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            });
        }
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $materials  = $query->paginate(30)->withQueryString();
        $categories = InventoryCategory::orderBy('name')->get();
        $units      = InventoryUnit::orderBy('name')->get();
        $isAdmin    = $this->isAdmin();

        return view('inventory.index', compact('materials', 'categories', 'units', 'q', 'categoryId', 'isAdmin'));
    }

    public function searchMaterials(Request $request)
    {
        $this->checkAccess();

        $q = $request->get('q', '');

        $results = InventoryMaterial::with(['unit', 'category'])
            ->where('name', 'like', "%{$q}%")
            ->orderBy('name')
            ->limit(12)
            ->get()
            ->map(fn ($m) => [
                'id'       => $m->id,
                'name'     => $m->name,
                'category' => $m->category?->name,
                'unit'     => $m->unit?->abbreviation ?? '',
                'price'    => $m->price_per_unit,
                'stock'    => $m->current_stock,
            ]);

        return response()->json($results);
    }

    public function storeMaterial(Request $request)
    {
        $this->checkAccess();
        if (! $this->isAdmin()) abort(403);

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:120'],
            'sku'            => ['nullable', 'string', 'max:40', 'unique:inventory_materials,sku'],
            'category_id'    => ['nullable', 'integer', 'exists:inventory_categories,id'],
            'unit_id'        => ['required', 'integer', 'exists:inventory_units,id'],
            'price_per_unit' => ['required', 'numeric', 'min:0'],
            'current_stock'  => ['required', 'numeric', 'min:0'],
            'reorder_level'  => ['nullable', 'numeric', 'min:0'],
            'description'    => ['nullable', 'string', 'max:500'],
        ]);

        $data['reorder_level'] = $data['reorder_level'] ?? 0;
        if (empty($data['sku'])) {
            unset($data['sku']);
        }

        $material = InventoryMaterial::create($data);

        if ($request->expectsJson()) {
            $material->load('category', 'unit');
            return response()->json(['success' => true, 'material' => $material]);
        }

        return back()->with('success', "Material '{$material->name}' added.");
    }

    public function updateMaterial(Request $request, InventoryMaterial $material)
    {
        $this->checkAccess();
        if (! $this->isAdmin()) abort(403);

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:120'],
            'sku'            => ['nullable', 'string', 'max:40', 'unique:inventory_materials,sku,'.$material->id],
            'category_id'    => ['nullable', 'integer', 'exists:inventory_categories,id'],
            'unit_id'        => ['required', 'integer', 'exists:inventory_units,id'],
            'price_per_unit' => ['required', 'numeric', 'min:0'],
            'current_stock'  => ['required', 'numeric', 'min:0'],
            'reorder_level'  => ['nullable', 'numeric', 'min:0'],
            'description'    => ['nullable', 'string', 'max:500'],
        ]);

        $data['reorder_level'] = $data['reorder_level'] ?? 0;
        if (empty($data['sku'])) {
            $data['sku'] = null;
        }

        $material->update($data);

        return response()->json(['success' => true]);
    }

    public function destroyMaterial(InventoryMaterial $material)
    {
        $this->checkAccess();
        if (! $this->isAdmin()) abort(403);

        if ($material->jobUsages()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Material is used in production jobs.']);
        }

        $material->delete();

        return response()->json(['success' => true]);
    }

    // ── Categories ────────────────────────────────────────────────────────────

    public function categories()
    {
        $this->checkAccess();
        if (! $this->isAdmin()) abort(403);

        $categories = InventoryCategory::withCount('materials')->orderBy('name')->get();
        return view('inventory.categories', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $this->checkAccess();
        if (! $this->isAdmin()) abort(403);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:200'],
        ]);

        $cat = InventoryCategory::create($data);
        return response()->json(['success' => true, 'category' => $cat]);
    }

    public function updateCategory(Request $request, InventoryCategory $category)
    {
        $this->checkAccess();
        if (! $this->isAdmin()) abort(403);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:200'],
        ]);

        $category->update($data);
        return response()->json(['success' => true]);
    }

    public function destroyCategory(InventoryCategory $category)
    {
        $this->checkAccess();
        if (! $this->isAdmin()) abort(403);

        if ($category->materials()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Category has materials. Remove or reassign them first.']);
        }

        $category->delete();
        return response()->json(['success' => true]);
    }

    // ── Units ─────────────────────────────────────────────────────────────────

    public function units()
    {
        $this->checkAccess();
        if (! $this->isAdmin()) abort(403);

        $units = InventoryUnit::withCount('materials')->orderBy('name')->get();
        return view('inventory.units', compact('units'));
    }

    public function storeUnit(Request $request)
    {
        $this->checkAccess();
        if (! $this->isAdmin()) abort(403);

        $data = $request->validate([
            'name'         => ['required', 'string', 'max:50'],
            'abbreviation' => ['required', 'string', 'max:10'],
        ]);

        $unit = InventoryUnit::create($data);
        return response()->json(['success' => true, 'unit' => $unit]);
    }

    public function updateUnit(Request $request, InventoryUnit $unit)
    {
        $this->checkAccess();
        if (! $this->isAdmin()) abort(403);

        $data = $request->validate([
            'name'         => ['required', 'string', 'max:50'],
            'abbreviation' => ['required', 'string', 'max:10'],
        ]);

        $unit->update($data);
        return response()->json(['success' => true]);
    }

    public function destroyUnit(InventoryUnit $unit)
    {
        $this->checkAccess();
        if (! $this->isAdmin()) abort(403);

        if ($unit->materials()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Unit is in use by materials.']);
        }

        $unit->delete();
        return response()->json(['success' => true]);
    }
}
