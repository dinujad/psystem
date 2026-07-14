@extends('layouts.app')
@section('title', 'Raw Materials')

@section('css')
<style>
.inv-page { padding: 0 20px 60px; }
.inv-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin: 20px 0 18px; }
.inv-title { font-size: 20px; font-weight: 800; color: #1e1b4b; display: flex; align-items: center; gap: 10px; }
.inv-subtitle { font-size: 13px; color: #6b7280; font-weight: 500; margin-top: 4px; }
.inv-btn   { background: #7c5cfc; color: #fff; border: none; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
.inv-btn:hover { background: #5b3fd9; color: #fff; text-decoration: none; }
.inv-btn.outline { background: #fff; color: #7c5cfc; border: 1.5px solid #7c5cfc; }
.inv-btn.outline:hover { background: #ede9fe; }
.inv-btn.danger { background: #fee2e2; color: #ef4444; }
.inv-btn.danger:hover { background: #fecaca; }

.inv-setup-banner { background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; font-size: 13px; color: #92400e; }
.inv-setup-banner a { color: #7c5cfc; font-weight: 700; }

.inv-bar { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px 16px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 18px; }
.inv-bar input, .inv-bar select { border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 13px; background: #f9fafb; color: #374151; }
.inv-bar input:focus, .inv-bar select:focus { outline: none; border-color: #7c5cfc; background: #fff; }

.inv-table-wrap { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
.inv-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.inv-table th { text-align: left; padding: 12px 14px; background: #f9fafb; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
.inv-table td { padding: 12px 14px; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: middle; }
.inv-table tr:last-child td { border-bottom: none; }
.inv-table tr:hover td { background: #fafafa; }
.inv-cat-tag { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background: #ede9fe; color: #7c5cfc; }
.inv-stock-low { color: #ef4444; font-weight: 700; }
.inv-stock-warn { color: #d97706; font-weight: 700; }
.inv-price { font-weight: 700; color: #111827; }
.inv-sku { font-size: 11px; color: #9ca3af; font-family: monospace; }

.inv-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(17, 24, 39, .55);
    z-index: 100000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    box-sizing: border-box;
    overflow-y: auto;
}
.inv-modal-overlay.show { display: flex !important; }
.inv-modal {
    background: #fff;
    border-radius: 18px;
    padding: 28px;
    width: 100%;
    max-width: 520px;
    max-height: calc(100vh - 32px);
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,.3);
    margin: auto;
}
.inv-modal h3 { font-size: 17px; font-weight: 800; color: #111827; margin: 0 0 6px; }
.inv-modal-sub { font-size: 12px; color: #9ca3af; margin: 0 0 18px; }
.inv-field { margin-bottom: 14px; }
.inv-field label { display: block; font-size: 12px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 5px; }
.inv-field input, .inv-field select, .inv-field textarea { width: 100%; border: 1px solid #d1d5db; border-radius: 9px; padding: 9px 12px; font-size: 13px; color: #374151; background: #f9fafb; outline: none; box-sizing: border-box; }
.inv-field input:focus, .inv-field select:focus { border-color: #7c5cfc; background: #fff; }
.inv-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.inv-field-hint { font-size: 11px; color: #9ca3af; margin-top: 4px; }
.inv-modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; }
.inv-modal-actions button { border: none; border-radius: 10px; padding: 10px 22px; font-size: 13px; font-weight: 700; cursor: pointer; }
.inv-modal-save { background: #7c5cfc; color: #fff; }
.inv-modal-save:hover { background: #5b3fd9; }
.inv-modal-cancel { background: #f3f4f6; color: #374151; }
.inv-err { font-size: 12px; color: #ef4444; margin-top: 10px; }

/* Success popup */
.inv-success-modal { max-width: 400px; text-align: center; padding: 0; overflow: hidden; }
.inv-success-body { padding: 36px 28px 20px; }
.inv-success-icon {
    width: 72px; height: 72px; margin: 0 auto 16px;
    background: #dcfce7; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 36px; line-height: 1;
}
.inv-success-title { font-size: 20px; font-weight: 800; color: #111827; margin: 0 0 8px; }
.inv-success-msg { font-size: 14px; color: #6b7280; margin: 0; line-height: 1.5; }
.inv-success-foot { padding: 16px 28px 24px; border-top: 1px solid #f3f4f6; }
.inv-success-btn {
    width: 100%; background: #16a34a; color: #fff; border: none;
    border-radius: 10px; padding: 12px 20px; font-size: 14px; font-weight: 700; cursor: pointer;
}
.inv-success-btn:hover { background: #15803d; }

.inv-toast { position: fixed; bottom: 28px; right: 24px; background: #111827; color: #fff; border-radius: 12px; padding: 12px 20px; font-size: 13px; font-weight: 600; box-shadow: 0 8px 24px rgba(0,0,0,.2); z-index: 9999; display: none; }
.inv-toast.show { display: block; }
@media (max-width: 640px) { .inv-field-row { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="inv-page">

    <div class="inv-head">
        <div>
            <div class="inv-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#7c5cfc" stroke-width="2"><path d="M12 3l8 4.5v9l-8 4.5l-8-4.5v-9z"/><path d="M12 12l8-4.5M12 12v9M12 12l-8-4.5"/></svg>
                Raw Materials
            </div>
            <div class="inv-subtitle">Production inventory — purchase cost &amp; stock for job issuing (not for direct sale)</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('inventory.purchases.index') }}" class="inv-btn outline">Material Purchases</a>
            <a href="{{ route('inventory.purchases.create') }}" class="inv-btn outline">+ Purchase Stock</a>
            @if($isAdmin)
            <a href="{{ route('inventory.units') }}" class="inv-btn outline">Units</a>
            <a href="{{ route('inventory.categories') }}" class="inv-btn outline">Categories</a>
            <button onclick="openAddModal()" class="inv-btn">+ Add Raw Material</button>
            @endif
        </div>
    </div>

    @if($isAdmin && ($units->isEmpty() || $categories->isEmpty()))
    <div class="inv-setup-banner">
        @if($units->isEmpty())
        ⚠ No units yet — <a href="{{ route('inventory.units') }}">add units</a> (pcs, kg, sheet…) before adding materials.
        @endif
        @if($categories->isEmpty())
        @if($units->isEmpty()) · @endif
        No categories — <a href="{{ route('inventory.categories') }}">add categories</a> to organize materials.
        @endif
    </div>
    @endif

    <form method="GET" class="inv-bar">
        <input type="text" name="q" value="{{ $q }}" placeholder="Search name or code…" style="flex:1;min-width:180px;">
        <select name="category_id">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @selected($categoryId == $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="inv-btn">Filter</button>
        @if($q || $categoryId)
        <a href="{{ route('inventory.index') }}" class="inv-btn outline">Clear</a>
        @endif
    </form>

    <div class="inv-table-wrap">
        <table class="inv-table">
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Cost Price</th>
                    <th>Stock</th>
                    <th>Stock Value</th>
                    @if($isAdmin) <th></th> @endif
                </tr>
            </thead>
            <tbody>
                @forelse($materials as $mat)
                @php
                    $low = $mat->isLowStock();
                    $out = $mat->current_stock <= 0;
                @endphp
                <tr>
                    <td>
                        @if($mat->sku)<div class="inv-sku">{{ $mat->sku }}</div>@endif
                        <div style="font-weight:700;color:#111827;">{{ $mat->name }}</div>
                        @if($mat->description)
                        <div style="font-size:11px;color:#9ca3af;margin-top:2px;">{{ Str::limit($mat->description, 50) }}</div>
                        @endif
                    </td>
                    <td>@if($mat->category)<span class="inv-cat-tag">{{ $mat->category->name }}</span>@else — @endif</td>
                    <td>{{ $mat->unit ? $mat->unit->abbreviation : '—' }}</td>
                    <td class="inv-price">Rs {{ number_format($mat->price_per_unit, 2) }}<span style="font-size:10px;color:#9ca3af;font-weight:400;"> /{{ $mat->unit?->abbreviation ?? 'unit' }}</span></td>
                    <td class="{{ $out ? 'inv-stock-low' : ($low ? 'inv-stock-warn' : '') }}">
                        {{ number_format($mat->current_stock, 2) }} {{ $mat->unit?->abbreviation }}
                        @if($out)<span style="font-size:10px;"> (out)</span>
                        @elseif($low && $mat->reorder_level > 0)<span style="font-size:10px;"> (low)</span>
                        @endif
                    </td>
                    <td style="font-weight:600;">Rs {{ number_format($mat->stockValue(), 2) }}</td>
                    @if($isAdmin)
                    <td>
                        <div style="display:flex;gap:6px;">
                            <button onclick='openEditModal(@json($mat))' class="inv-btn outline" style="padding:5px 10px;font-size:11px;">Edit</button>
                            <button onclick="deleteMaterial({{ $mat->id }}, '{{ addslashes($mat->name) }}')" class="inv-btn danger" style="padding:5px 10px;font-size:11px;">Delete</button>
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ $isAdmin ? 7 : 6 }}" style="text-align:center;padding:40px;color:#9ca3af;">No raw materials yet. @if($isAdmin)<a onclick="openAddModal()" style="color:#7c5cfc;cursor:pointer;"> Add your first material</a>.@endif</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($materials->hasPages())
    <div style="margin-top:16px;">{{ $materials->links() }}</div>
    @endif

</div>

@if($isAdmin)
<div class="inv-modal-overlay" id="materialModal">
    <div class="inv-modal">
        <h3 id="modal-title">Add Raw Material</h3>
        <p class="inv-modal-sub">Cost price is used when issuing materials to production jobs.</p>
        <input type="hidden" id="mat-id">
        <div class="inv-field">
            <label>Material Name <span style="color:#ef4444">*</span></label>
            <input type="text" id="mat-name" placeholder="e.g. Glossy Art Paper A4 300gsm">
        </div>
        <div class="inv-field-row">
            <div class="inv-field">
                <label>Material Code / SKU</label>
                <input type="text" id="mat-sku" placeholder="e.g. PAP-A4-300" maxlength="40">
                <div class="inv-field-hint">Optional unique code</div>
            </div>
            <div class="inv-field">
                <label>Category</label>
                <select id="mat-category">
                    <option value="">— None —</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="inv-field-row">
            <div class="inv-field">
                <label>Unit <span style="color:#ef4444">*</span></label>
                <select id="mat-unit" required>
                    <option value="">— Select unit —</option>
                    @foreach($units as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->abbreviation }})</option>
                    @endforeach
                </select>
                @if($units->isEmpty())
                <div class="inv-field-hint"><a href="{{ route('inventory.units') }}">Add units first</a></div>
                @endif
            </div>
            <div class="inv-field">
                <label>Cost Price (Rs) <span style="color:#ef4444">*</span></label>
                <input type="number" id="mat-price" step="0.01" min="0" placeholder="0.00">
                <div class="inv-field-hint">Per unit purchase cost</div>
            </div>
        </div>
        <div class="inv-field-row">
            <div class="inv-field">
                <label>Opening Stock <span style="color:#ef4444">*</span></label>
                <input type="number" id="mat-stock" step="0.001" min="0" placeholder="0">
            </div>
            <div class="inv-field">
                <label>Reorder Level</label>
                <input type="number" id="mat-reorder" step="0.001" min="0" placeholder="0">
                <div class="inv-field-hint">Alert when stock at or below this</div>
            </div>
        </div>
        <div class="inv-field">
            <label>Description / Spec</label>
            <textarea id="mat-desc" rows="2" placeholder="Size, color, brand, supplier notes…"></textarea>
        </div>
        <p class="inv-err" id="mat-err"></p>
        <div class="inv-modal-actions">
            <button class="inv-modal-cancel" onclick="closeMaterialModal()">Cancel</button>
            <button class="inv-modal-save" onclick="saveMaterial()">Save Material</button>
        </div>
    </div>
</div>
@endif

{{-- Success popup --}}
<div class="inv-modal-overlay" id="successModal">
    <div class="inv-modal inv-success-modal">
        <div class="inv-success-body">
            <div class="inv-success-icon">✓</div>
            <h3 class="inv-success-title">Success!</h3>
            <p class="inv-success-msg" id="successMsg">Material saved successfully.</p>
        </div>
        <div class="inv-success-foot">
            <button type="button" class="inv-success-btn" id="successDoneBtn">Done</button>
        </div>
    </div>
</div>

<div id="invToast" class="inv-toast"></div>
@endsection

@if($isAdmin)
@section('javascript')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const MAT_URL = '{{ url('inventory/materials') }}';

function toast(msg, isError = false) {
    const t = document.getElementById('invToast');
    t.textContent = msg;
    t.style.background = isError ? '#ef4444' : '#111827';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2800);
}

function openAddModal() {
    document.getElementById('modal-title').textContent = 'Add Raw Material';
    document.getElementById('mat-id').value = '';
    ['mat-name','mat-sku','mat-price','mat-stock','mat-reorder','mat-desc'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('mat-category').value = '';
    document.getElementById('mat-unit').value = '';
    document.getElementById('mat-err').textContent = '';
    document.getElementById('materialModal').classList.add('show');
}

function openEditModal(mat) {
    document.getElementById('modal-title').textContent = 'Edit Raw Material';
    document.getElementById('mat-id').value = mat.id;
    document.getElementById('mat-name').value = mat.name;
    document.getElementById('mat-sku').value = mat.sku || '';
    document.getElementById('mat-category').value = mat.category_id || '';
    document.getElementById('mat-unit').value = mat.unit_id || '';
    document.getElementById('mat-price').value = mat.price_per_unit;
    document.getElementById('mat-stock').value = mat.current_stock;
    document.getElementById('mat-reorder').value = mat.reorder_level || 0;
    document.getElementById('mat-desc').value = mat.description || '';
    document.getElementById('mat-err').textContent = '';
    document.getElementById('materialModal').classList.add('show');
}

function closeMaterialModal() {
    document.getElementById('materialModal').classList.remove('show');
}

function showSuccessModal(message) {
    document.getElementById('successMsg').textContent = message;
    document.getElementById('successModal').classList.add('show');
}

function closeSuccessModal(reload = true) {
    document.getElementById('successModal').classList.remove('show');
    if (reload) location.reload();
}

document.getElementById('successDoneBtn')?.addEventListener('click', () => closeSuccessModal(true));

document.getElementById('materialModal')?.addEventListener('click', e => {
    if (e.target.id === 'materialModal') closeMaterialModal();
});
document.getElementById('successModal')?.addEventListener('click', e => {
    if (e.target.id === 'successModal') closeSuccessModal(true);
});

async function saveMaterial() {
    const id = document.getElementById('mat-id').value;
    const name = document.getElementById('mat-name').value.trim();
    const unitId = document.getElementById('mat-unit').value;
    const errEl = document.getElementById('mat-err');

    if (!name) { errEl.textContent = 'Material name is required.'; return; }
    if (!unitId) { errEl.textContent = 'Please select a unit.'; return; }
    if (!document.getElementById('mat-price').value) { errEl.textContent = 'Cost price is required.'; return; }

    const body = {
        name,
        sku: document.getElementById('mat-sku').value.trim() || null,
        category_id: document.getElementById('mat-category').value || null,
        unit_id: unitId,
        price_per_unit: document.getElementById('mat-price').value,
        current_stock: document.getElementById('mat-stock').value || 0,
        reorder_level: document.getElementById('mat-reorder').value || 0,
        description: document.getElementById('mat-desc').value.trim() || null,
    };

    const url = id ? `${MAT_URL}/${id}` : MAT_URL;
    const method = id ? 'PUT' : 'POST';

    const r = await fetch(url, {
        method,
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(body),
    });
    const d = await r.json();

    if (d.success || d.material) {
        closeMaterialModal();
        const msg = id
            ? `"${name}" updated successfully.`
            : `"${name}" added to raw materials.`;
        showSuccessModal(msg);
    } else {
        errEl.textContent = d.message || Object.values(d.errors || {}).flat().join(', ') || 'Error.';
    }
}

async function deleteMaterial(id, name) {
    if (!confirm(`Delete "${name}"?`)) return;
    const r = await fetch(`${MAT_URL}/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    });
    const d = await r.json();
    if (d.success) { toast('Deleted.'); setTimeout(() => location.reload(), 700); }
    else toast(d.message || 'Failed.', true);
}
</script>
@endsection
@endif
