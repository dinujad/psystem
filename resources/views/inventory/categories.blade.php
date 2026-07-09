@extends('layouts.app')
@section('title', 'Raw Materials — Categories')

@section('css')
<style>
.inv-page   { padding: 0 20px 60px; max-width: 860px; margin: 0 auto; }
.inv-back   { display: inline-flex; align-items: center; gap: 6px; color: #7c5cfc; font-size: 13px; font-weight: 600; text-decoration: none; margin: 18px 0 14px; }
.inv-hero   { background: linear-gradient(135deg, #1e1b4b, #4f46e5); border-radius: 14px; padding: 22px 26px; color: #fff; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
.inv-hero h1 { font-size: 20px; font-weight: 800; margin: 0; }
.inv-btn    { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.3); border-radius: 9px; padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; }
.inv-btn:hover { background: rgba(255,255,255,.25); color: #fff; text-decoration: none; }
.inv-card   { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
.inv-cat-list { display: flex; flex-direction: column; }
.inv-cat-row { display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-bottom: 1px solid #f3f4f6; }
.inv-cat-row:last-child { border-bottom: none; }
.inv-cat-icon { width: 36px; height: 36px; border-radius: 9px; background: #ede9fe; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.inv-cat-name { font-weight: 700; font-size: 14px; color: #111827; }
.inv-cat-count { font-size: 12px; color: #9ca3af; margin-top: 2px; }
.inv-cat-actions { margin-left: auto; display: flex; gap: 6px; }
.inv-btn-sm { border: none; border-radius: 8px; padding: 5px 12px; font-size: 11px; font-weight: 700; cursor: pointer; }
.inv-btn-edit { background: #ede9fe; color: #7c5cfc; }
.inv-btn-del  { background: #fee2e2; color: #ef4444; }
.inv-empty    { text-align: center; padding: 40px; color: #9ca3af; font-size: 13px; }

.inv-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 9000; align-items: center; justify-content: center; }
.inv-modal-overlay.show { display: flex; }
.inv-modal  { background: #fff; border-radius: 16px; padding: 26px; width: 400px; max-width: 95vw; }
.inv-modal h3 { font-size: 16px; font-weight: 800; margin: 0 0 16px; }
.inv-field  { margin-bottom: 13px; }
.inv-field label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #374151; margin-bottom: 5px; }
.inv-field input, .inv-field textarea { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 13px; box-sizing: border-box; }
.inv-field input:focus, .inv-field textarea:focus { outline: none; border-color: #7c5cfc; }
.inv-modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 16px; }
.inv-modal-actions button { border: none; border-radius: 9px; padding: 9px 20px; font-size: 13px; font-weight: 700; cursor: pointer; }
.inv-save   { background: #7c5cfc; color: #fff; }
.inv-cancel { background: #f3f4f6; color: #374151; }
.inv-err    { font-size: 12px; color: #ef4444; margin: 8px 0 0; }
.inv-toast  { position: fixed; bottom: 28px; right: 24px; background: #111827; color: #fff; border-radius: 12px; padding: 12px 20px; font-size: 13px; font-weight: 600; z-index: 9999; display: none; }
.inv-toast.show { display: block; }
</style>
@endsection

@section('content')
<div class="inv-page">
    <a href="{{ route('inventory.index') }}" class="inv-back">← Back to Raw Materials</a>

    <div class="inv-hero">
        <h1>📁 Material Categories</h1>
        <button onclick="openAdd()" class="inv-btn">+ New Category</button>
    </div>

    <div class="inv-card">
        <div class="inv-cat-list">
            @forelse($categories as $cat)
            <div class="inv-cat-row" data-id="{{ $cat->id }}">
                <div class="inv-cat-icon">📁</div>
                <div>
                    <div class="inv-cat-name">{{ $cat->name }}</div>
                    <div class="inv-cat-count">{{ $cat->materials_count }} material(s) @if($cat->description)— {{ $cat->description }}@endif</div>
                </div>
                <div class="inv-cat-actions">
                    <button class="inv-btn-sm inv-btn-edit" onclick='openEdit(@json($cat))'>Edit</button>
                    <button class="inv-btn-sm inv-btn-del" onclick="doDelete({{ $cat->id }}, '{{ addslashes($cat->name) }}')">Delete</button>
                </div>
            </div>
            @empty
            <div class="inv-empty">No categories yet. <a onclick="openAdd()" style="color:#7c5cfc;cursor:pointer;">Add one</a>.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="inv-modal-overlay" id="catModal">
    <div class="inv-modal">
        <h3 id="catModalTitle">New Category</h3>
        <input type="hidden" id="cat-id">
        <div class="inv-field">
            <label>Name *</label>
            <input type="text" id="cat-name" placeholder="e.g. Paper, Ink, Finishing">
        </div>
        <div class="inv-field">
            <label>Description</label>
            <textarea id="cat-desc" rows="2" placeholder="Optional description"></textarea>
        </div>
        <p class="inv-err" id="cat-err"></p>
        <div class="inv-modal-actions">
            <button class="inv-cancel" onclick="document.getElementById('catModal').classList.remove('show')">Cancel</button>
            <button class="inv-save" onclick="saveCategory()">Save</button>
        </div>
    </div>
</div>
<div id="invToast" class="inv-toast"></div>
@endsection

@section('javascript')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const CAT_URL = '{{ url('inventory/categories') }}';

function toast(msg, err) {
    const t = document.getElementById('invToast');
    t.textContent = msg;
    t.style.background = err ? '#ef4444' : '#111827';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
}

function openAdd() {
    document.getElementById('catModalTitle').textContent = 'New Category';
    document.getElementById('cat-id').value = '';
    document.getElementById('cat-name').value = '';
    document.getElementById('cat-desc').value = '';
    document.getElementById('cat-err').textContent = '';
    document.getElementById('catModal').classList.add('show');
}

function openEdit(cat) {
    document.getElementById('catModalTitle').textContent = 'Edit Category';
    document.getElementById('cat-id').value = cat.id;
    document.getElementById('cat-name').value = cat.name;
    document.getElementById('cat-desc').value = cat.description || '';
    document.getElementById('cat-err').textContent = '';
    document.getElementById('catModal').classList.add('show');
}

async function saveCategory() {
    const id = document.getElementById('cat-id').value;
    const name = document.getElementById('cat-name').value.trim();
    const errEl = document.getElementById('cat-err');
    if (!name) { errEl.textContent = 'Name is required.'; return; }
    const body = { name, description: document.getElementById('cat-desc').value.trim() || null };
    const url = id ? `${CAT_URL}/${id}` : CAT_URL;
    const method = id ? 'PUT' : 'POST';
    const r = await fetch(url, { method, headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(body) });
    const d = await r.json();
    if (d.success || d.category) { toast(id ? 'Updated!' : 'Added!'); setTimeout(() => location.reload(), 700); }
    else errEl.textContent = d.message || 'Error.';
}

async function doDelete(id, name) {
    if (!confirm(`Delete category "${name}"?`)) return;
    const r = await fetch(`${CAT_URL}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
    const d = await r.json();
    if (d.success) { toast('Deleted.'); setTimeout(() => location.reload(), 700); }
    else toast(d.message, true);
}
</script>
@endsection
