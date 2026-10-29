@extends('layouts.app')
@section('title', 'Purchase Raw Materials')

@section('css')
<style>
.rmp-page { padding: 0 20px 60px; max-width: 980px; margin: 0 auto; }
.rmp-hero {
    border-radius: 16px; padding: 22px 26px; margin: 18px 0;
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 55%, #0ea5e9 100%);
    color: #fff !important; box-shadow: 0 4px 20px rgba(15,23,42,.22);
}
.rmp-hero h1 { margin: 0 0 6px; font-size: 22px; font-weight: 800; color: #fff !important; }
.rmp-hero p { margin: 0; font-size: 13px; opacity: .92; color: #fff !important; }
.rmp-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 18px 20px; margin-bottom: 14px; }
.rmp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.rmp-field label { display: block; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 5px; }
.rmp-field input, .rmp-field select, .rmp-field textarea {
    width: 100%; border: 1px solid #d1d5db; border-radius: 9px; padding: 9px 12px; font-size: 13px; background: #fff; color: #111827;
}
.rmp-note {
    background: #ecfeff; border: 1px solid #a5f3fc; color: #155e75; border-radius: 10px;
    padding: 10px 14px; font-size: 13px; margin-bottom: 14px;
}
.rmp-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.rmp-table th { text-align: left; font-size: 11px; color: #6b7280; text-transform: uppercase; padding: 8px 6px; border-bottom: 1px solid #e5e7eb; }
.rmp-table td { padding: 8px 6px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
.rmp-table input, .rmp-table select { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 7px 9px; font-size: 13px; background: #fff; color: #111827; }
body #scrollable-container .rmp-page input,
body #scrollable-container .rmp-page select,
body #scrollable-container .rmp-page textarea,
body .rmp-modal input,
body .rmp-modal select,
body .rmp-modal textarea,
body #scrollable-container .rmp-page select option,
body .rmp-modal select option {
    color-scheme: light !important;
    background: #fff !important;
    background-color: #fff !important;
    color: #111827 !important;
    border-color: #d1d5db !important;
    -webkit-text-fill-color: #111827 !important;
}
body #scrollable-container .rmp-page input::placeholder,
body .rmp-modal input::placeholder,
body .rmp-modal textarea::placeholder {
    color: #6b7280 !important;
    -webkit-text-fill-color: #6b7280 !important;
}
.rmp-modal-ov { position: fixed; inset: 0; background: rgba(17,24,39,.5); z-index: 100000; display: none; }
.rmp-modal-ov.show { display: block !important; }
.rmp-modal {
    background: #fff; border-radius: 16px; width: calc(100% - 32px); max-width: 520px;
    max-height: calc(100vh - 32px); overflow-y: auto; padding: 18px 20px;
    position: fixed; left: 50%; top: 50%; transform: translate(-50%, -50%);
    margin: 0; z-index: 100001;
}
.rmp-modal h3 { margin: 0 0 4px; font-size: 17px; font-weight: 800; color: #111827; }
.rmp-modal p { margin: 0 0 14px; font-size: 12px; color: #6b7280; }
.rmp-err { color: #b91c1c; font-size: 12px; font-weight: 700; min-height: 16px; margin: 8px 0 0; }
.rmp-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; }
.rmp-btn { border: none; border-radius: 9px; padding: 10px 18px; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
.rmp-btn-primary { background: #0ea5e9; color: #fff; }
.rmp-btn-secondary { background: #f3f4f6; color: #374151; }
.rmp-btn-outline { background: #fff; color: #0ea5e9; border: 1.5px solid #0ea5e9; }
.rmp-total { text-align: right; font-size: 16px; font-weight: 800; margin-top: 12px; color: #111827; }
@media (max-width: 700px) { .rmp-grid { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="rmp-page">
    <div class="rmp-hero">
        <h1>Purchase Raw Materials</h1>
        <p>Buying sheet / ink / media for Production — stock goes to Raw Materials. No selling price or profit margin.</p>
    </div>

    <div class="rmp-note">
        Use this for materials you consume in Production.
        Finished goods are created later with <strong>Convert to Product</strong>, then sold.
        Do <strong>not</strong> use normal Product Purchase for raw materials.
    </div>

    @if($materials->isEmpty())
    <div class="alert alert-warning" style="border-radius:10px;">
        No raw materials yet. Use <strong>New material</strong> below to add one, then enter the quantity to purchase.
    </div>
    @endif

    <form method="POST" action="{{ route('inventory.purchases.store') }}" id="rmpForm" enctype="multipart/form-data">
        @csrf
        <div class="rmp-card">
            <div class="rmp-grid">
                <div class="rmp-field">
                    <label>Supplier</label>
                    <select name="contact_id">
                        <option value="">— Optional —</option>
                        @foreach($suppliers as $id => $name)
                            <option value="{{ $id }}" @selected(old('contact_id') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rmp-field">
                    <label>Purchase Date</label>
                    <input type="date" name="purchase_date" value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                </div>
                <div class="rmp-field">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="received" @selected(old('status', 'received') === 'received')>Received (add stock now)</option>
                        <option value="ordered" @selected(old('status') === 'ordered')>Ordered (stock later)</option>
                    </select>
                </div>
                <div class="rmp-field">
                    <label>Reference No</label>
                    <input type="text" name="ref_no" value="{{ old('ref_no') }}" placeholder="Auto if empty">
                </div>
            </div>
            <div class="rmp-grid" style="margin-top:12px;">
                <div class="rmp-field">
                    <label>Deposit to account</label>
                    <select name="accounting_deposit_to" class="accounts-dropdown" style="width:100%;">
                        <option value="">Use linked account from Accounting settings</option>
                    </select>
                </div>
                <div class="rmp-field">
                    <label>Payment account</label>
                    <select name="accounting_payment_account" class="accounts-dropdown" style="width:100%;">
                        <option value="">Use linked account from Accounting settings</option>
                    </select>
                </div>
            </div>
            <div class="rmp-grid" style="margin-top:12px;">
                <div class="rmp-field">
                    <label>Amount paid now</label>
                    <input type="number" name="amount_paid" min="0" step="0.01" value="{{ old('amount_paid', '0') }}" placeholder="0 if nothing paid yet">
                </div>
                <div class="rmp-field">
                    <label>Payment method</label>
                    <select name="payment_method" id="rmpCreateMethod">
                        <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option>
                        <option value="cheque" @selected(old('payment_method') === 'cheque')>Cheque</option>
                        <option value="bank" @selected(old('payment_method') === 'bank')>Bank transfer</option>
                        <option value="card" @selected(old('payment_method') === 'card')>Card</option>
                    </select>
                </div>
            </div>
            <div class="rmp-field" id="rmpCreateCheque" style="margin-top:12px;display:none;">
                <label>Cheque number</label>
                <input type="text" name="cheque_number" value="{{ old('cheque_number') }}" placeholder="Cheque number">
            </div>
            <div id="rmpCreateBank" style="margin-top:12px;display:none;">
                <div class="rmp-grid">
                    <div class="rmp-field">
                        <label>Transfer id / reference</label>
                        <input type="text" name="reference_no" value="{{ old('reference_no') }}" placeholder="Bank reference or transfer id">
                    </div>
                    <div class="rmp-field">
                        <label>Transfer slip (PDF, JPG, or PNG)</label>
                        <input type="file" name="payment_attachment" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>
            </div>
            <div class="rmp-field" style="margin-top:12px;">
                <label>Notes</label>
                <textarea name="notes" rows="2" placeholder="Optional">{{ old('notes') }}</textarea>
            </div>
            @error('amount_paid')
                <p style="color:#b91c1c;font-weight:700;margin-top:8px;">{{ $message }}</p>
            @enderror
        </div>

        <div class="rmp-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <strong style="font-size:15px;">Materials</strong>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button type="button" class="rmp-btn rmp-btn-outline" onclick="openNewMaterial()">+ New material</button>
                    <button type="button" class="rmp-btn rmp-btn-outline" onclick="addLine()">+ Add line</button>
                </div>
            </div>
            <table class="rmp-table">
                <thead>
                    <tr>
                        <th style="width:42%;">Raw Material</th>
                        <th style="width:16%;">Qty</th>
                        <th style="width:18%;">Purchase cost / unit</th>
                        <th style="width:16%;">Line total</th>
                        <th style="width:8%;"></th>
                    </tr>
                </thead>
                <tbody id="rmpLines"></tbody>
            </table>
            <div class="rmp-total">Total: Rs. <span id="rmpTotal">0.00</span></div>
        </div>

        <div class="rmp-actions">
            <button type="submit" class="rmp-btn rmp-btn-primary">Save Purchase</button>
            <a href="{{ route('inventory.purchases.index') }}" class="rmp-btn rmp-btn-secondary">Cancel</a>
            <a href="{{ route('inventory.index') }}" class="rmp-btn rmp-btn-secondary">Raw Materials list</a>
        </div>
    </form>
</div>

<div class="rmp-modal-ov" id="rmpNewMaterial">
    <div class="rmp-modal">
        <h3>Add raw material</h3>
        <p>This saves the material, then selects it on this purchase. Stock is added from the quantity on the line, not from this popup.</p>
        <div class="rmp-field">
            <label>Material name</label>
            <input type="text" id="nmName" placeholder="e.g. Glossy Art Paper A4 300gsm">
        </div>
        <div class="rmp-grid" style="margin-top:10px;">
            <div class="rmp-field">
                <label>Code / SKU</label>
                <input type="text" id="nmSku" maxlength="40" placeholder="Optional">
            </div>
            <div class="rmp-field">
                <label>Category</label>
                <select id="nmCategory">
                    <option value="">— None —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="rmp-field">
                <label>Unit</label>
                <select id="nmUnit">
                    <option value="">— Select unit —</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                    @endforeach
                </select>
            </div>
            <div class="rmp-field">
                <label>Purchase cost / unit</label>
                <input type="number" id="nmPrice" step="0.01" min="0" placeholder="0.00">
            </div>
        </div>
        @if($units->isEmpty())
            <p class="rmp-err">Add a unit first under Raw Materials → Units.</p>
        @endif
        <p class="rmp-err" id="nmErr"></p>
        <div class="rmp-actions">
            <button type="button" class="rmp-btn rmp-btn-primary" id="nmSave">Save and use</button>
            <button type="button" class="rmp-btn rmp-btn-secondary" onclick="closeNewMaterial()">Cancel</button>
        </div>
    </div>
</div>
<style>
body #scrollable-container .rmp-page input,
body #scrollable-container .rmp-page select,
body #scrollable-container .rmp-page textarea,
body .rmp-modal input,
body .rmp-modal select,
body .rmp-modal textarea,
body #scrollable-container .rmp-page select option,
body .rmp-modal select option {
    color-scheme: light !important;
    background: #ffffff !important;
    background-color: #ffffff !important;
    color: #111827 !important;
    border: 1px solid #d1d5db !important;
    -webkit-text-fill-color: #111827 !important;
}
</style>
@endsection

@section('javascript')
<script>
const MATERIALS = @json($materialsJson);

let lineIdx = 0;

function esc(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
    });
}

function materialOptions(selected) {
    let html = '<option value="">Select material…</option>';
    MATERIALS.forEach(function (m) {
        const label = m.name
            + (m.sku ? (' [' + m.sku + ']') : '')
            + (m.unit ? (' (' + m.unit + ')') : '')
            + ' — stock ' + m.stock;
        const sel = String(selected) === String(m.id) ? ' selected' : '';
        html += '<option value="' + m.id + '" data-price="' + m.price + '"' + sel + '>' + esc(label) + '</option>';
    });
    return html;
}

let targetSelect = null;

function openNewMaterial(selectEl) {
    targetSelect = selectEl || null;
    document.getElementById('nmErr').textContent = '';
    document.getElementById('rmpNewMaterial').classList.add('show');
    document.getElementById('nmName').focus();
}

function closeNewMaterial() {
    document.getElementById('rmpNewMaterial').classList.remove('show');
    targetSelect = null;
}

function refreshSelects(prefer) {
    document.querySelectorAll('#rmpLines select[name*="[material_id]"]').forEach(function (sel) {
        const keep = sel === prefer ? prefer.value : sel.value;
        sel.innerHTML = materialOptions(keep);
    });
}

function addLine(materialId, qty, cost) {
    materialId = materialId || '';
    qty = qty || '';
    cost = cost || '';
    const i = lineIdx++;
    const tr = document.createElement('tr');
    tr.innerHTML = ''
        + '<td><select name="lines[' + i + '][material_id]" required onchange="onMaterialChange(this, ' + i + ')">'
        + materialOptions(materialId)
        + '</select></td>'
        + '<td><input type="number" step="0.0001" min="0.0001" name="lines[' + i + '][quantity]" value="' + qty + '" required oninput="recalc()"></td>'
        + '<td><input type="number" step="0.01" min="0" name="lines[' + i + '][unit_cost]" id="cost_' + i + '" value="' + cost + '" required oninput="recalc()"></td>'
        + '<td><strong id="line_total_' + i + '">0.00</strong></td>'
        + '<td><button type="button" class="rmp-btn rmp-btn-secondary" onclick="this.closest(\'tr\').remove(); recalc();">✕</button></td>';
    document.getElementById('rmpLines').appendChild(tr);
    recalc();
}

function onMaterialChange(sel, i) {
    const opt = sel.options[sel.selectedIndex];
    const price = opt?.dataset?.price;
    if (price !== undefined && price !== '') {
        const costInput = document.getElementById('cost_' + i);
        if (costInput && (!costInput.value || Number(costInput.value) === 0)) {
            costInput.value = Number(price).toFixed(2);
        }
    }
    recalc();
}

function recalc() {
    let total = 0;
    document.querySelectorAll('#rmpLines tr').forEach((tr, idx) => {
        const qty = parseFloat(tr.querySelector('[name*="[quantity]"]')?.value || 0);
        const cost = parseFloat(tr.querySelector('[name*="[unit_cost]"]')?.value || 0);
        const line = qty * cost;
        total += line;
        const strong = tr.querySelector('strong[id^="line_total_"]');
        if (strong) strong.textContent = line.toFixed(2);
    });
    document.getElementById('rmpTotal').textContent = total.toFixed(2);
}

document.getElementById('rmpForm').addEventListener('submit', function (e) {
    if (!document.querySelectorAll('#rmpLines tr').length) {
        e.preventDefault();
        alert('Add at least one raw material line.');
    }
});

document.getElementById('rmpNewMaterial').addEventListener('click', function (e) {
    if (e.target.id === 'rmpNewMaterial') closeNewMaterial();
});

document.getElementById('nmSave').addEventListener('click', function () {
    const err = document.getElementById('nmErr');
    const name = document.getElementById('nmName').value.trim();
    const unitId = document.getElementById('nmUnit').value;
    const price = document.getElementById('nmPrice').value;
    if (!name) { err.textContent = 'Material name is required.'; return; }
    if (!unitId) { err.textContent = 'Select a unit.'; return; }
    if (price === '') { err.textContent = 'Purchase cost is required.'; return; }

    const btn = this;
    btn.disabled = true;
    const selectForNew = targetSelect;
    fetch("{{ route('inventory.purchases.materials.store') }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            name: name,
            sku: document.getElementById('nmSku').value.trim() || null,
            category_id: document.getElementById('nmCategory').value || null,
            unit_id: unitId,
            price_per_unit: price
        })
    }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
    .then(function (res) {
        btn.disabled = false;
        if (!res.ok || !res.d.material) {
            const errors = res.d.errors || {};
            const first = Object.keys(errors).length ? errors[Object.keys(errors)[0]][0] : (res.d.message || 'Could not save the material.');
            err.textContent = first;
            return;
        }
        MATERIALS.push(res.d.material);
        MATERIALS.sort(function (a, b) { return String(a.name).localeCompare(String(b.name)); });
        let sel = selectForNew;
        if (!sel || !document.body.contains(sel)) {
            const empty = Array.from(document.querySelectorAll('#rmpLines select[name*="[material_id]"]')).find(function (s) { return !s.value; });
            if (empty) {
                sel = empty;
            } else {
                addLine(res.d.material.id, '', Number(res.d.material.price).toFixed(2));
                sel = null;
            }
        }
        if (sel) {
            sel.innerHTML = materialOptions(res.d.material.id);
            const row = sel.closest('tr');
            const costInput = row ? row.querySelector('[name*="[unit_cost]"]') : null;
            if (costInput && (!costInput.value || Number(costInput.value) === 0)) {
                costInput.value = Number(res.d.material.price).toFixed(2);
            }
            refreshSelects(sel);
        } else {
            refreshSelects(null);
        }
        document.getElementById('nmName').value = '';
        document.getElementById('nmSku').value = '';
        document.getElementById('nmPrice').value = '';
        document.getElementById('nmCategory').value = '';
        document.getElementById('nmUnit').value = '';
        closeNewMaterial();
        recalc();
    }).catch(function () {
        btn.disabled = false;
        err.textContent = 'Could not save the material.';
    });
});

function toggleCreatePayFields() {
    var method = document.getElementById('rmpCreateMethod').value;
    document.getElementById('rmpCreateCheque').style.display = method === 'cheque' ? 'block' : 'none';
    document.getElementById('rmpCreateBank').style.display = method === 'bank' ? 'block' : 'none';
}
document.getElementById('rmpCreateMethod').addEventListener('change', toggleCreatePayFields);
toggleCreatePayFields();

addLine();
</script>
@endsection
