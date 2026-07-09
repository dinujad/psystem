@extends('layouts.app')
@section('title', 'Plan Templates')

@section('css')
<style>
.wt-page{padding:0 20px 60px;max-width:1000px;margin:0 auto}
.wt-head{display:flex;justify-content:space-between;align-items:center;margin:20px 0 16px;flex-wrap:wrap;gap:10px}
.wt-title{font-size:20px;font-weight:800;color:#1e1b4b}
.wt-btn{background:#7c5cfc;color:#fff;border:none;border-radius:9px;padding:8px 14px;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.wt-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px 18px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.wt-card h3{margin:0 0 4px;font-size:15px;font-weight:800;color:#111827}
.wt-card p{margin:0;font-size:12px;color:#6b7280}
.wt-actions{display:flex;gap:8px;flex-wrap:wrap}
.wt-actions a,.wt-actions button{font-size:12px;font-weight:700;border-radius:8px;padding:6px 12px;border:none;cursor:pointer;text-decoration:none}
</style>
@endsection

@section('content')
<div class="wt-page">
    <div class="wt-head">
        <div class="wt-title">Weekly Plan Templates</div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('employee-todos.index') }}" class="wt-btn" style="background:#fff;color:#7c5cfc;border:1.5px solid #7c5cfc;">← Weekly Planner</a>
            <a href="{{ route('employee-todos.templates.create') }}" class="wt-btn">+ New Template</a>
        </div>
    </div>

    @forelse($templates as $t)
    <div class="wt-card">
        <div>
            <h3>{{ $t->name }}</h3>
            <p>{{ $t->items_count }} tasks @if($t->description)· {{ Str::limit($t->description, 80) }}@endif</p>
        </div>
        <div class="wt-actions">
            <a href="{{ route('employee-todos.templates.edit', $t) }}" style="background:#ede9fe;color:#5b21b6;">Edit</a>
            <button type="button" style="background:#dbeafe;color:#1d4ed8;" onclick="duplicateTpl({{ $t->id }})">Duplicate</button>
            <button type="button" style="background:#fee2e2;color:#dc2626;" onclick="deleteTpl({{ $t->id }})">Delete</button>
        </div>
    </div>
    @empty
    <div style="text-align:center;padding:40px;color:#9ca3af;background:#fff;border:1px dashed #e5e7eb;border-radius:14px;">No templates yet. Create one to assign weekly plans to employees.</div>
    @endforelse
</div>
@endsection

@section('javascript')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
function duplicateTpl(id){
    fetch(`/employee-todos/templates/${id}/duplicate`, {method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
    .then(r=>r.json()).then(d=>{ if(d.redirect) location.href=d.redirect; });
}
function deleteTpl(id){
    if(!confirm('Delete this template?')) return;
    fetch(`/employee-todos/templates/${id}`, {method:'DELETE',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
    .then(r=>r.json()).then(d=>{ if(d.success) location.reload(); });
}
</script>
@endsection
