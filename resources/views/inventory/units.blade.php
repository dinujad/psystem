@extends('layouts.app')
@section('title', 'Raw Materials — Units')

@section('css')
<style>
.inv-page   { padding: 0 20px 60px; max-width: 760px; margin: 0 auto; }
.inv-back   { display: inline-flex; align-items: center; gap: 6px; color: #7c5cfc; font-size: 13px; font-weight: 600; text-decoration: none; margin: 18px 0 14px; }
.inv-hero   { background: linear-gradient(135deg, #1e1b4b, #4f46e5); border-radius: 14px; padding: 22px 26px; color: #fff; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
.inv-hero h1 { font-size: 20px; font-weight: 800; margin: 0; }
.inv-btn    { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.3); border-radius: 9px; padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer; }
.inv-btn:hover { background: rgba(255,255,255,.25); }
.inv-card   { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
.inv-units-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; padding: 16px; }
.inv-unit-card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px 16px; display: flex; align-items: center; gap: 12px; }
.inv-unit-abbr { width: 44px; height: 44px; border-radius: 10px; background: #ede9fe; color: #7c5cfc; font-size: 14px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.inv-unit-name { font-size: 13px; font-weight: 700; color: #111827; }
.inv-unit-count { font-size: 11px; color: #9ca3af; }
.inv-unit-actions { margin-top: 6px; display: flex; gap: 5px; }
.inv-btn-sm { border: none; border-radius: 6px; padding: 3px 10px; font-size: 11px; font-weight: 700; cursor: pointer; }
.inv-btn-edit { background: #ede9fe; color: #7c5cfc; }
.inv-btn-del  { background: #fee2e2; color: #ef4444; }
.inv-empty    { text-align: center; padding: 40px; color: #9ca3af; }

.inv-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 9000; align-items: center; justify-content: center; }
.inv-modal-overlay.show { display: flex; }
.inv-modal  { background: #fff; border-radius: 16px; padding: 24px; width: 380px; max-width: 95vw; }
.inv-modal h3 { font-size: 16px; font-weight: 800; margin: 0 0 14px; }
.inv-field  { margin-bottom: 12px; }
.inv-field label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #374151; margin-bottom: 4px; }
.inv-field input { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 13px; box-sizing: border-box; }
.inv-field input:focus { outline: none; border-color: #7c5cfc; }
.inv-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.inv-modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 14px; }
.inv-modal-actions button { border: none; border-radius: 8px; padding: 8px 18px; font-size: 13px; font-weight: 700; cursor: pointer; }
.inv-save   { background: #7c5cfc; color: #fff; }
.inv-cancel { background: #f3f4f6; color: #374151; }
.inv-err    { font-size: 12px; color: #ef4444; margin: 6px 0 0; }
.inv-toast  { position: fixed; bottom: 28px; right: 24px; background: #111827; color: #fff; border-radius: 12px; padding: 12px 20px; font-size: 13px; font-weight: 600; z-index: 9999; display: none; }
.inv-toast.show { display: block; }
</style>
@endsection

@section('content')
<div class="inv-page">
    <a href="{{ route('inventory.index') }}" class="inv-back">← Back to Raw Materials</a>
    <div class="inv-hero">
        <h1>📐 Measurement Units</h1>
        <button onclick="openAdd()" class="inv-btn">+ New Unit</button>
    </div>
    <div class="inv-card">
        @if($units->count())
        <div class="inv-units-grid">
            @foreach($units as $unit)
            <div class="inv-unit-card">
                <div class="inv-unit-abbr">{{ $unit->abbreviation }}</div>
                <div>
                    <div class="inv-unit-name">{{ $unit->name }}</div>
                    <div class="inv-unit-count">{{ $unit->materials_count }} material(s)</div>
                    <div class="inv-unit-actions">
                        <button class="inv-btn-sm inv-btn-edit" onclick='openEdit(@json($unit))'>Edit</button>
                        <button class="inv-btn-sm inv-btn-del" onclick="doDelete({{ $unit->id }}, '{{ addslashes($unit->name) }}')">Delete</button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="inv-empty">No units yet. <a onclick="openAdd()" style="color:#7c5cfc;cursor:pointer;">Add one</a>.</div>
        @endif
    </div>
</div>

<div class="inv-modal-overlay" id="unitModal">
    <div class="inv-modal">
        <h3 id="unitModalTitle">New Unit</h3>
        <input type="hidden" id="unit-id">
        <div class="inv-field-row">
            <div class="inv-field">
                <label>Unit Name *</label>
                <input type="text" id="unit-name" placeholder="e.g. Pieces">
            </div>
            <div class="inv-field">
                <label>Abbreviation *</label>
                <input type="text" id="unit-abbr" placeholder="pcs" maxlength="10">
            </div>
        </div>
        <p class="inv-err" id="unit-err"></p>
        <div class="inv-modal-actions">
            <button class="inv-cancel" onclick="document.getElementById('unitModal').classList.remove('show')">Cancel</button>
            <button class="inv-save" onclick="saveUnit()">Save</button>
        </div>
    </div>
</div>
<div id="invToast" class="inv-toast"></div>
@endsection

@section('javascript')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const UNIT_URL = '{{ url('inventory/units') }}';

function toast(msg, err) {
    const t = document.getElementById('invToast'); t.textContent = msg;
    t.style.background = err ? '#ef4444' : '#111827'; t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
}
function openAdd() {
    document.getElementById('unitModalTitle').textContent = 'New Unit';
    document.getElementById('unit-id').value = '';
    document.getElementById('unit-name').value = '';
    document.getElementById('unit-abbr').value = '';
    document.getElementById('unit-err').textContent = '';
    document.getElementById('unitModal').classList.add('show');
}
function openEdit(u) {
    document.getElementById('unitModalTitle').textContent = 'Edit Unit';
    document.getElementById('unit-id').value = u.id;
    document.getElementById('unit-name').value = u.name;
    document.getElementById('unit-abbr').value = u.abbreviation;
    document.getElementById('unit-err').textContent = '';
    document.getElementById('unitModal').classList.add('show');
}
async function saveUnit() {
    const id = document.getElementById('unit-id').value;
    const name = document.getElementById('unit-name').value.trim();
    const abbr = document.getElementById('unit-abbr').value.trim();
    const errEl = document.getElementById('unit-err');
    if (!name || !abbr) { errEl.textContent = 'Both fields required.'; return; }
    const body = { name, abbreviation: abbr };
    const url = id ? `${UNIT_URL}/${id}` : UNIT_URL;
    const r = await fetch(url, { method: id ? 'PUT' : 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(body) });
    const d = await r.json();
    if (d.success || d.unit) { toast(id ? 'Updated!' : 'Added!'); setTimeout(() => location.reload(), 700); }
    else errEl.textContent = d.message || 'Error.';
}
async function doDelete(id, name) {
    if (!confirm(`Delete unit "${name}"?`)) return;
    const r = await fetch(`${UNIT_URL}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
    const d = await r.json();
    if (d.success) { toast('Deleted.'); setTimeout(() => location.reload(), 700); } else toast(d.message, true);
}
</script>
@endsection
