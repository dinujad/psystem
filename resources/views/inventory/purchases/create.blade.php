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
    width: 100%; border: 1px solid #d1d5db; border-radius: 9px; padding: 9px 12px; font-size: 13px; background: #f9fafb;
}
.rmp-note {
    background: #ecfeff; border: 1px solid #a5f3fc; color: #155e75; border-radius: 10px;
    padding: 10px 14px; font-size: 13px; margin-bottom: 14px;
}
.rmp-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.rmp-table th { text-align: left; font-size: 11px; color: #6b7280; text-transform: uppercase; padding: 8px 6px; border-bottom: 1px solid #e5e7eb; }
.rmp-table td { padding: 8px 6px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
.rmp-table input, .rmp-table select { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 7px 9px; font-size: 13px; }
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
        No raw materials found. First add materials under
        <a href="{{ route('inventory.index') }}" style="font-weight:700;">Raw Materials</a>,
        then come back to purchase stock.
    </div>
    @endif

    <form method="POST" action="{{ route('inventory.purchases.store') }}" id="rmpForm">
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
            <div class="rmp-field" style="margin-top:12px;">
                <label>Notes</label>
                <textarea name="notes" rows="2" placeholder="Optional">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="rmp-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <strong style="font-size:15px;">Materials</strong>
                <button type="button" class="rmp-btn rmp-btn-outline" onclick="addLine()">+ Add line</button>
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
@endsection

@section('javascript')
<script>
const MATERIALS = @json($materialsJson);

let lineIdx = 0;

function materialOptions(selected) {
    let html = '<option value="">Select material…</option>';
    MATERIALS.forEach(function (m) {
        const label = m.name
            + (m.sku ? (' [' + m.sku + ']') : '')
            + (m.unit ? (' (' + m.unit + ')') : '')
            + ' — stock ' + m.stock;
        const sel = String(selected) === String(m.id) ? ' selected' : '';
        html += '<option value="' + m.id + '" data-price="' + m.price + '"' + sel + '>' + label + '</option>';
    });
    return html;
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

addLine();
</script>
@endsection
