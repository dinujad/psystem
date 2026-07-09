@extends('layouts.app')
@section('title', $template->exists ? 'Edit Template' : 'New Template')

@section('css')
<style>
.te-page{padding:0 16px 60px;max-width:100%;margin:0 auto}
.te-head{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px;margin:20px 0 14px}
.te-title{font-size:20px;font-weight:800;color:#1e1b4b}
.te-meta{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;margin-bottom:14px;display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:700px){.te-meta{grid-template-columns:1fr}}
.te-meta label{font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;display:block;margin-bottom:4px}
.te-meta input,.te-meta textarea{width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px 10px;font-size:13px;box-sizing:border-box}
.te-grid-wrap{overflow-x:auto;background:#fff;border:1px solid #e5e7eb;border-radius:14px}
.te-grid{width:100%;min-width:1100px;border-collapse:collapse}
.te-grid th{background:#f9fafb;padding:8px;font-size:10px;font-weight:800;text-transform:uppercase;color:#6b7280;border-bottom:1px solid #e5e7eb;text-align:center}
.te-grid th.te-cat{text-align:left;min-width:120px;position:sticky;left:0;z-index:2;background:#f9fafb}
.te-grid td{border:1px solid #f3f4f6;vertical-align:top;padding:6px;min-width:130px}
.te-grid td.te-cat{position:sticky;left:0;background:#fff;z-index:1;font-size:12px;font-weight:800;border-right:1px solid #e5e7eb}
.te-cat-dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:6px}
.te-item{background:#f9fafb;border:1px solid #e5e7eb;border-radius:7px;padding:6px;margin-bottom:5px;font-size:11px}
.te-item input{width:100%;border:1px solid #e5e7eb;border-radius:5px;padding:4px 6px;font-size:11px;margin-bottom:4px;box-sizing:border-box}
.te-item-row{display:flex;gap:4px}
.te-item-row input{flex:1}
.te-add{width:100%;border:1px dashed #d1d5db;background:transparent;border-radius:6px;padding:4px;font-size:10px;font-weight:700;color:#9ca3af;cursor:pointer}
.te-add:hover{border-color:#7c5cfc;color:#7c5cfc}
.te-foot{margin-top:14px;display:flex;gap:10px;justify-content:flex-end}
.te-btn{background:#7c5cfc;color:#fff;border:none;border-radius:9px;padding:10px 20px;font-size:13px;font-weight:700;cursor:pointer}
.te-btn.outline{background:#fff;color:#7c5cfc;border:1.5px solid #7c5cfc}
</style>
@endsection

@section('content')
<div class="te-page" x-data="templateBuilder()" x-init="init()">
    <div class="te-head">
        <div class="te-title">{{ $template->exists ? 'Edit Template' : 'New Template' }}</div>
        <a href="{{ route('employee-todos.templates.index') }}" class="te-btn outline" style="text-decoration:none;">← Templates</a>
    </div>

    <div class="te-meta">
        <div>
            <label>Template Name *</label>
            <input type="text" x-model="name" placeholder="e.g. Social Media Weekly">
        </div>
        <div>
            <label>Description</label>
            <input type="text" x-model="description" placeholder="Optional">
        </div>
    </div>

    <div class="te-grid-wrap">
        <table class="te-grid">
            <thead>
                <tr>
                    <th class="te-cat">Category</th>
                    @foreach($days as $num => $day)
                    <th>{{ $day['short'] }}<br><small style="font-weight:600;color:#9ca3af;">{{ $day['label'] }}</small></th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <template x-for="cat in categories" :key="cat.id">
                    <tr>
                        <td class="te-cat"><span class="te-cat-dot" :style="'background:'+cat.color"></span><span x-text="cat.name"></span></td>
                        <template x-for="day in days" :key="day.num">
                            <td>
                                <template x-for="(item, idx) in cellItems(cat.id, day.num)" :key="item._key">
                                    <div class="te-item">
                                        <input type="text" x-model="item.title" placeholder="Task title">
                                        <div class="te-item-row">
                                            <input type="time" x-model="item.task_time" title="Time">
                                            <input type="number" x-model.number="item.checklist_count" min="1" max="99" title="Checklist count" style="width:50px;">
                                        </div>
                                        <button type="button" style="font-size:10px;color:#dc2626;border:none;background:none;cursor:pointer;padding:0;" @click="removeItem(cat.id, day.num, idx)">Remove</button>
                                    </div>
                                </template>
                                <button type="button" class="te-add" @click="addItem(cat.id, day.num)">+ Add</button>
                            </td>
                        </template>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div class="te-foot">
        <button type="button" class="te-btn outline" @click="save(false)" :disabled="saving">Save</button>
        <button type="button" class="te-btn" @click="save(true)" :disabled="saving">Save & Close</button>
    </div>
</div>
@endsection

@section('javascript')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
<script>
function templateBuilder(){
    const base = @json($builder);
    return {
        name: base.name,
        description: base.description,
        categories: base.categories,
        days: base.days,
        items: base.items,
        saving: false,
        templateId: base.templateId,
        saveUrl: base.saveUrl,
        method: base.method,
        csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
        init(){},
        cellItems(catId, day){
            return this.items.filter(i => i.category_id == catId && i.day_of_week == day);
        },
        addItem(catId, day){
            this.items.push({
                _key: 'n'+Date.now()+Math.random(),
                category_id: catId,
                day_of_week: day,
                title: '',
                task_time: '',
                checklist_count: 1
            });
        },
        removeItem(catId, day, idx){
            const cell = this.cellItems(catId, day);
            const target = cell[idx];
            this.items = this.items.filter(i => i._key !== target._key);
        },
        payloadItems(){
            return this.items.filter(i => (i.title||'').trim()).map(i => ({
                category_id: i.category_id,
                day_of_week: i.day_of_week,
                title: i.title.trim(),
                task_time: i.task_time || null,
                checklist_count: i.checklist_count || 1
            }));
        },
        async save(closeAfter){
            if(!this.name.trim()){ alert('Template name is required'); return; }
            this.saving = true;
            try {
                const r = await fetch(this.saveUrl, {
                    method: this.method,
                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':this.csrf,'Accept':'application/json'},
                    body: JSON.stringify({ name: this.name, description: this.description, items: this.payloadItems() })
                });
                const d = await r.json();
                if(!d.success && !d.template_id){ alert('Save failed'); return; }
                if(closeAfter) location.href = '{{ route("employee-todos.templates.index") }}';
                else if(!this.templateId && d.template_id) location.href = '/employee-todos/templates/'+d.template_id+'/edit';
                else alert('Saved');
            } finally { this.saving = false; }
        }
    };
}
</script>
@endsection
