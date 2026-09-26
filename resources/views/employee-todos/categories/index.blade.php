@extends('layouts.app')
@section('title', 'Task Categories')

@section('css')
<style>
.tc-page{padding:0 20px 60px;max-width:900px;margin:0 auto}
.tc-head{display:flex;justify-content:space-between;align-items:center;margin:20px 0 16px;flex-wrap:wrap;gap:10px}
.tc-title{font-size:20px;font-weight:800;color:#1e1b4b;display:flex;align-items:center;gap:10px}
.tc-title i{color:#7c5cfc}
.tc-btn{background:#7c5cfc;color:#fff;border:none;border-radius:9px;padding:8px 14px;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.tc-btn:hover{background:#5b3fd9;color:#fff;text-decoration:none}
.tc-btn.outline{background:#fff;color:#7c5cfc;border:1.5px solid #7c5cfc}
.tc-btn.outline:hover{background:#ede9fe}
.tc-list{background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden}
.tc-row{display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #f3f4f6}
.tc-row:last-child{border-bottom:none}
.tc-dot{width:14px;height:14px;border-radius:50%;flex-shrink:0}
.tc-name{flex:1;font-size:14px;font-weight:700;color:#111827}
.tc-inactive{opacity:.5}
.tc-actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.tc-actions button{border:none;border-radius:7px;padding:5px 10px;font-size:11px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:4px}
.tc-order{display:flex;flex-direction:column;gap:2px}
.tc-order button{width:24px;height:20px;border:1px solid #e5e7eb;background:#f9fafb;border-radius:4px;font-size:11px;cursor:pointer;line-height:1;color:#6b7280;padding:0}
.tc-order button:hover{border-color:#7c5cfc;color:#7c5cfc}
.tc-modal-ov{
    position:fixed !important;
    top:0 !important; left:0 !important; right:0 !important; bottom:0 !important;
    width:100vw !important; height:100vh !important;
    margin:0 !important;
    background:rgba(17,24,39,.55);
    z-index:100000 !important;
    display:none;
    align-items:center;
    justify-content:center;
    padding:16px;
    box-sizing:border-box;
}
.tc-modal-ov.show{display:flex !important;}
.tc-modal{
    background:#fff;
    border-radius:16px;
    width:100%;
    max-width:420px;
    max-height:calc(100vh - 32px);
    overflow-y:auto;
    margin:auto;
    box-shadow:0 20px 60px rgba(0,0,0,.25);
    position:relative;
}
.tc-modal-head{background:linear-gradient(135deg,#1e1b4b,#4f46e5);color:#fff;padding:16px 20px;border-radius:16px 16px 0 0;display:flex;align-items:center;justify-content:space-between}
.tc-modal-head h3{margin:0;font-size:16px;font-weight:800;color:#fff;display:flex;align-items:center;gap:8px}
.tc-modal-close{background:transparent;border:none;color:#fff;font-size:18px;cursor:pointer;opacity:.85}
.tc-modal-body{padding:16px 20px}
.tc-field{margin-bottom:14px}
.tc-label{font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;display:block}
.tc-input{width:100%;border:1px solid #d1d5db;border-radius:8px;padding:10px 12px;font-size:14px;box-sizing:border-box;background:#fff;color:#111827}
.tc-input:focus{outline:none;border-color:#7c5cfc;box-shadow:0 0 0 3px rgba(124,92,252,.15)}
.tc-color-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.tc-color-row input[type=color]{width:44px;height:36px;border:1px solid #d1d5db;border-radius:8px;padding:2px;background:#fff;cursor:pointer}
.tc-swatches{display:flex;gap:6px;flex-wrap:wrap}
.tc-swatch{width:24px;height:24px;border-radius:50%;border:2px solid transparent;cursor:pointer;padding:0}
.tc-swatch.active{border-color:#111827;box-shadow:0 0 0 2px #fff,0 0 0 3px #7c5cfc}
.tc-modal-foot{padding:14px 20px;border-top:1px solid #f3f4f6;display:flex;gap:8px;justify-content:flex-end}
.tc-empty{padding:30px;text-align:center;color:#9ca3af}
</style>
@endsection

@section('content')
<div class="tc-page">
    <div class="tc-head">
        <div class="tc-title"><i class="fas fa-tags"></i> Task Categories</div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('employee-todos.index') }}" class="tc-btn outline">
                <i class="fas fa-arrow-left"></i> Weekly Planner
            </a>
            <button type="button" class="tc-btn" onclick="openAdd()">
                <i class="fas fa-plus"></i> Add Category
            </button>
        </div>
    </div>
    <div class="tc-list" id="catList">
        @forelse($categories as $cat)
        <div class="tc-row {{ $cat->is_active ? '' : 'tc-inactive' }}" data-id="{{ $cat->id }}">
            <div class="tc-order">
                <button type="button" onclick="moveCat({{ $cat->id }}, -1)" title="Move up"><i class="fas fa-chevron-up"></i></button>
                <button type="button" onclick="moveCat({{ $cat->id }}, 1)" title="Move down"><i class="fas fa-chevron-down"></i></button>
            </div>
            <div class="tc-dot" style="background:{{ $cat->color }};"></div>
            <div class="tc-name">{{ $cat->name }} @unless($cat->is_active)<small>(inactive)</small>@endunless</div>
            <div class="tc-actions">
                <button style="background:#ede9fe;color:#5b21b6;" onclick="editCat({{ json_encode($cat) }})">
                    <i class="fas fa-pen"></i> Edit
                </button>
                @if($cat->is_active)
                <button style="background:#fef3c7;color:#b45309;" onclick="deactivateCat({{ $cat->id }})">
                    <i class="fas fa-pause"></i> Deactivate
                </button>
                @else
                <button style="background:#dcfce7;color:#15803d;" onclick="activateCat({{ $cat->id }})">
                    <i class="fas fa-play"></i> Activate
                </button>
                @endif
                <button style="background:#fee2e2;color:#dc2626;" onclick="removeCat({{ $cat->id }})">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </div>
        </div>
        @empty
        <div class="tc-empty">No categories yet. Click <strong>Add Category</strong> to create one.</div>
        @endforelse
    </div>
</div>

<div class="tc-modal-ov" id="catModal" onclick="if(event.target===this) closeModal()">
    <div class="tc-modal" role="dialog" aria-modal="true">
        <div class="tc-modal-head">
            <h3 id="catModalTitle"><i class="fas fa-tag"></i> Add Category</h3>
            <button type="button" class="tc-modal-close" onclick="closeModal()" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form id="catForm" onsubmit="return saveCat(event)">
            <div class="tc-modal-body">
                <input type="hidden" id="catId" value="">
                <div class="tc-field">
                    <label class="tc-label" for="catName">Category Name *</label>
                    <input type="text" id="catName" class="tc-input" maxlength="80" placeholder="e.g. Social Media, Sales, Production" required autofocus>
                </div>
                <div class="tc-field">
                    <label class="tc-label">Color</label>
                    <div class="tc-color-row">
                        <input type="color" id="catColor" value="#7c5cfc" onchange="syncSwatches()">
                        <div class="tc-swatches" id="catSwatches">
                            @foreach(['#7c5cfc','#E31E24','#F9A810','#15803d','#2563eb','#db2777','#0f766e','#111827'] as $c)
                            <button type="button" class="tc-swatch" data-color="{{ $c }}" style="background:{{ $c }};" onclick="pickColor('{{ $c }}')" title="{{ $c }}"></button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="tc-modal-foot">
                <button type="button" class="tc-btn outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="tc-btn" id="catSaveBtn"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('javascript')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
const CAT_ORDER = @json($categories->pluck('id'));

function ensureModalOnBody(){
    const modal = document.getElementById('catModal');
    if(modal && modal.parentElement !== document.body){
        document.body.appendChild(modal);
    }
}

document.addEventListener('DOMContentLoaded', ensureModalOnBody);

function openAdd(){
    ensureModalOnBody();
    document.getElementById('catId').value = '';
    document.getElementById('catName').value = '';
    document.getElementById('catColor').value = '#7c5cfc';
    document.getElementById('catModalTitle').innerHTML = '<i class="fas fa-tag"></i> Add Category';
    syncSwatches();
    document.getElementById('catModal').classList.add('show');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('catName').focus(), 50);
}
function editCat(cat){
    ensureModalOnBody();
    document.getElementById('catId').value = cat.id;
    document.getElementById('catName').value = cat.name || '';
    document.getElementById('catColor').value = cat.color || '#7c5cfc';
    document.getElementById('catModalTitle').innerHTML = '<i class="fas fa-pen"></i> Edit Category';
    syncSwatches();
    document.getElementById('catModal').classList.add('show');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('catName').focus(), 50);
}
function closeModal(){
    document.getElementById('catModal').classList.remove('show');
    document.body.style.overflow = '';
}
function pickColor(hex){
    document.getElementById('catColor').value = hex;
    syncSwatches();
}
function syncSwatches(){
    const val = (document.getElementById('catColor').value || '').toLowerCase();
    document.querySelectorAll('.tc-swatch').forEach(btn => {
        btn.classList.toggle('active', (btn.dataset.color || '').toLowerCase() === val);
    });
}
async function saveCat(e){
    e.preventDefault();
    const id = document.getElementById('catId').value;
    const name = document.getElementById('catName').value.trim();
    const color = document.getElementById('catColor').value || '#7c5cfc';
    if(!name){ document.getElementById('catName').focus(); return false; }

    const btn = document.getElementById('catSaveBtn');
    btn.disabled = true;
    try {
        const url = id ? `/employee-todos/categories/${id}` : '{{ route("employee-todos.categories.store") }}';
        const method = id ? 'PUT' : 'POST';
        const r = await fetch(url, {
            method,
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({name, color})
        });
        const d = await r.json();
        if(d.success) location.reload();
        else alert(d.message || 'Failed to save category');
    } catch (err) {
        alert('Failed to save category');
    } finally {
        btn.disabled = false;
    }
    return false;
}
function deactivateCat(id){
    fetch(`/employee-todos/categories/${id}`, {
        method:'PUT', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
        body: JSON.stringify({is_active: false})
    }).then(()=>location.reload());
}
function activateCat(id){
    fetch(`/employee-todos/categories/${id}`, {
        method:'PUT', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
        body: JSON.stringify({is_active: true})
    }).then(()=>location.reload());
}
function removeCat(id){
    if(!confirm('Remove this category? If in use it will be deactivated instead.')) return;
    fetch(`/employee-todos/categories/${id}`, {
        method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}
    }).then(r=>r.json()).then(d=>{ if(d.message) alert(d.message); location.reload(); });
}
function moveCat(id, dir){
    const order = [...CAT_ORDER];
    const idx = order.indexOf(id);
    const swap = idx + dir;
    if(swap < 0 || swap >= order.length) return;
    [order[idx], order[swap]] = [order[swap], order[idx]];
    fetch('{{ route("employee-todos.categories.reorder") }}', {
        method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
        body: JSON.stringify({order})
    }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); });
}
document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeModal();
});
</script>
@endsection
