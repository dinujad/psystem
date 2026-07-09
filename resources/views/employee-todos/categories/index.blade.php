@extends('layouts.app')
@section('title', 'Task Categories')

@section('css')
<style>
.tc-page{padding:0 20px 60px;max-width:900px;margin:0 auto}
.tc-head{display:flex;justify-content:space-between;align-items:center;margin:20px 0 16px;flex-wrap:wrap;gap:10px}
.tc-title{font-size:20px;font-weight:800;color:#1e1b4b}
.tc-btn{background:#7c5cfc;color:#fff;border:none;border-radius:9px;padding:8px 14px;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none}
.tc-list{background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden}
.tc-row{display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #f3f4f6}
.tc-row:last-child{border-bottom:none}
.tc-dot{width:14px;height:14px;border-radius:50%;flex-shrink:0}
.tc-name{flex:1;font-size:14px;font-weight:700;color:#111827}
.tc-inactive{opacity:.5}
.tc-actions{display:flex;gap:6px;align-items:center}
.tc-actions button{border:none;border-radius:7px;padding:5px 10px;font-size:11px;font-weight:700;cursor:pointer}
.tc-order{display:flex;flex-direction:column;gap:2px}
.tc-order button{width:22px;height:18px;border:1px solid #e5e7eb;background:#f9fafb;border-radius:4px;font-size:10px;cursor:pointer;line-height:1}
</style>
@endsection

@section('content')
<div class="tc-page">
    <div class="tc-head">
        <div class="tc-title">Task Categories</div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('employee-todos.index') }}" class="tc-btn" style="background:#fff;color:#7c5cfc;border:1.5px solid #7c5cfc;">← Weekly Planner</a>
            <button type="button" class="tc-btn" onclick="openAdd()">+ Add Category</button>
        </div>
    </div>
    <div class="tc-list" id="catList">
        @forelse($categories as $cat)
        <div class="tc-row {{ $cat->is_active ? '' : 'tc-inactive' }}" data-id="{{ $cat->id }}">
            <div class="tc-order">
                <button type="button" onclick="moveCat({{ $cat->id }}, -1)" title="Move up">▲</button>
                <button type="button" onclick="moveCat({{ $cat->id }}, 1)" title="Move down">▼</button>
            </div>
            <div class="tc-dot" style="background:{{ $cat->color }};"></div>
            <div class="tc-name">{{ $cat->name }} @unless($cat->is_active)<small>(inactive)</small>@endunless</div>
            <div class="tc-actions">
                <button style="background:#ede9fe;color:#5b21b6;" onclick="editCat({{ json_encode($cat) }})">Edit</button>
                @if($cat->is_active)
                <button style="background:#fef3c7;color:#b45309;" onclick="deactivateCat({{ $cat->id }})">Deactivate</button>
                @else
                <button style="background:#dcfce7;color:#15803d;" onclick="activateCat({{ $cat->id }})">Activate</button>
                @endif
                <button style="background:#fee2e2;color:#dc2626;" onclick="removeCat({{ $cat->id }})">Remove</button>
            </div>
        </div>
        @empty
        <div style="padding:30px;text-align:center;color:#9ca3af;">No categories yet.</div>
        @endforelse
    </div>
</div>
@endsection

@section('javascript')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
const CAT_ORDER = @json($categories->pluck('id'));

function openAdd(){
    const name = prompt('Category name:');
    if(!name) return;
    const color = prompt('Color (hex):', '#7c5cfc') || '#7c5cfc';
    fetch('{{ route("employee-todos.categories.store") }}', {
        method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
        body: JSON.stringify({name, color})
    }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert('Failed'); });
}
function editCat(cat){
    const name = prompt('Name:', cat.name);
    if(name===null) return;
    const color = prompt('Color:', cat.color) || cat.color;
    fetch(`/employee-todos/categories/${cat.id}`, {
        method:'PUT', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
        body: JSON.stringify({name, color})
    }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); });
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
    }).then(r=>r.json()).then(d=>{ alert(d.message || 'Done'); location.reload(); });
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
</script>
@endsection
