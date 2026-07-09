@extends('layouts.app')
@section('title', $personalOnly ? 'My To-Do' : 'Weekly To-Do')

@section('css')
<style>
.et-page { padding: 0 20px 60px; max-width: 1400px; margin: 0 auto; }
.et-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin: 20px 0 16px; }
.et-title { font-size: 20px; font-weight: 800; color: #1e1b4b; }
.et-week-nav { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.et-week-pill { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 8px 14px; font-size: 13px; font-weight: 700; color: #374151; }
.et-btn { background: #7c5cfc; color: #fff; border: none; border-radius: 9px; padding: 8px 14px; font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
.et-btn:hover { background: #5b3fd9; color: #fff; text-decoration: none; }
.et-btn.outline { background: #fff; color: #7c5cfc; border: 1.5px solid #7c5cfc; }
.et-btn.outline:hover { background: #ede9fe; }
.et-bar { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px 16px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 16px; }
.et-bar select, .et-bar input, .et-bar textarea { border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 13px; background: #fff !important; color: #111827 !important; }
body.theme-admin-pro .et-bar select,
body.theme-admin-pro .et-bar input,
body.theme-admin-pro .et-bar textarea {
    background: #fff !important;
    background-color: #fff !important;
    color: #111827 !important;
    border-color: #d1d5db !important;
}
.et-pct { display: inline-block; background: #ede9fe; color: #5b21b6; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 20px; margin-left: 6px; }
.et-pct.done { background: #dcfce7; color: #15803d; }
.et-grid-wrap { overflow-x: auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; }
.et-grid { width: 100%; min-width: 1100px; border-collapse: collapse; }
.et-grid th { background: #f9fafb; padding: 10px 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; border-bottom: 1px solid #e5e7eb; text-align: center; }
.et-grid th.et-cat-col { text-align: left; min-width: 130px; position: sticky; left: 0; z-index: 2; background: #f9fafb; }
.et-grid td { border-bottom: 1px solid #f3f4f6; border-right: 1px solid #f3f4f6; vertical-align: top; padding: 8px; min-width: 130px; }
.et-grid td.et-cat-col { position: sticky; left: 0; z-index: 1; background: #fff; border-right: 1px solid #e5e7eb; }
.et-cat { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 800; color: #111827; }
.et-cat-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.et-day-head small { display: block; font-size: 10px; color: #9ca3af; font-weight: 600; margin-top: 2px; }
.et-task { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 6px 8px; margin-bottom: 6px; font-size: 11px; }
.et-task.done { background: #f0fdf4; border-color: #86efac; opacity: .9; }
.et-task-row { display: flex; align-items: flex-start; gap: 6px; }
.et-task-row input[type=checkbox] { margin-top: 2px; flex-shrink: 0; cursor: pointer; }
.et-task-row input[type=checkbox]:disabled { cursor: default; opacity: 1; }
.et-task-title { font-weight: 700; color: #111827; line-height: 1.35; flex: 1; }
.et-task.done .et-task-title { text-decoration: line-through; color: #6b7280; }
.et-task-meta { font-size: 10px; color: #9ca3af; margin-top: 3px; display: flex; gap: 6px; flex-wrap: wrap; }
.et-task-del { border: none; background: none; color: #dc2626; font-size: 10px; cursor: pointer; padding: 0; margin-top: 3px; }
.et-add { width: 100%; border: 1px dashed #d1d5db; background: transparent; border-radius: 8px; padding: 5px; font-size: 10px; font-weight: 700; color: #9ca3af; cursor: pointer; }
.et-add:hover { border-color: #7c5cfc; color: #7c5cfc; background: #faf5ff; }
.et-modal-ov { position: fixed; inset: 0; width: 100vw; height: 100vh; background: rgba(17,24,39,.5); z-index: 100000; display: none; align-items: center; justify-content: center; padding: 16px; box-sizing: border-box; }
.et-modal-ov.show { display: flex !important; }
.et-modal { background: #fff; border-radius: 16px; width: 100%; max-width: 420px; max-height: calc(100vh - 32px); overflow-y: auto; margin: auto; box-shadow: 0 20px 60px rgba(0,0,0,.25); }
.et-modal-head { background: linear-gradient(135deg, #1e1b4b, #4f46e5); color: #fff; padding: 16px 20px; }
.et-modal-head h3 { margin: 0; font-size: 16px; font-weight: 800; color: #fff; }
.et-modal-body { padding: 16px 20px; background: #fff; }
.et-field { margin-bottom: 12px; }
.et-label { font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; display: block; }
.et-input { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 10px; font-size: 13px; box-sizing: border-box; background: #fff; color: #111827; }
.et-input:focus { outline: none; border-color: #7c5cfc; box-shadow: 0 0 0 3px rgba(124,92,252,.15); }
.et-modal .et-input,
.et-modal select,
.et-modal input,
.et-modal textarea,
body.theme-admin-pro .et-modal-ov .et-input,
body.theme-admin-pro .et-modal-ov select,
body.theme-admin-pro .et-modal-ov input,
body.theme-admin-pro .et-modal-ov textarea {
    background: #fff !important;
    background-color: #fff !important;
    color: #111827 !important;
    border-color: #d1d5db !important;
}
.et-modal-foot { padding: 14px 20px; border-top: 1px solid #f3f4f6; display: flex; gap: 8px; justify-content: flex-end; background: #fff; }
.et-toast { position: fixed; bottom: 24px; right: 24px; background: #111827; color: #fff; padding: 12px 18px; border-radius: 10px; font-size: 13px; font-weight: 600; z-index: 100001; display: none; }
.et-toast.show { display: block; }
.et-notes { flex: 1; min-width: 200px; }
.et-emp-badge { font-size: 13px; font-weight: 700; color: #5b21b6; background: #ede9fe; padding: 6px 12px; border-radius: 8px; }
.et-emp-banner{background:linear-gradient(135deg,#ede9fe,#f5f3ff);border:1px solid #c4b5fd;border-radius:12px;padding:12px 16px;margin-bottom:16px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px}
.et-emp-banner strong{color:#5b21b6;font-size:14px}
.et-emp-banner span{font-size:12px;color:#6b7280}
.et-assign-actions{display:flex;gap:8px;flex-wrap:wrap}
.et-hint{font-size:12px;color:#6b7280;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:10px 14px;margin-bottom:14px}
.et-hint strong{color:#15803d}
.et-admin-stats{font-size:12px;color:#6b7280;margin-left:8px}
.et-done-at{color:#15803d;font-size:10px}
.et-empty{padding:40px;text-align:center;color:#9ca3af;background:#fff;border:1px dashed #e5e7eb;border-radius:14px;margin-top:12px}
@media (max-width: 768px) { .et-grid { min-width: 800px; } }
</style>
@endsection

@section('content')
@php
    $hasItems = $plan && $plan->items->count() > 0;
@endphp
<div class="et-page">
    <div class="et-head">
        <div class="et-title">
            @if($personalOnly)
            ✅ My Weekly Tasks
            @else
            📋 Weekly To-Do
            @endif
            <span class="et-pct {{ ($weekStats['percent'] ?? 0) >= 100 ? 'done' : '' }}" id="weekPct">{{ $weekStats['percent'] ?? 0 }}%</span>
            @if($canManage && ($weekStats['total'] ?? 0) > 0)
            <span class="et-admin-stats" id="weekCount">{{ $weekStats['completed'] ?? 0 }}/{{ $weekStats['total'] ?? 0 }} done</span>
            @endif
        </div>
        <div class="et-week-nav">
            <a href="{{ route($personalOnly ? 'employee-todos.my-week' : 'employee-todos.index', array_filter(['week' => $prevWeek, 'employee' => $employeeId])) }}" class="et-btn outline">← Prev Week</a>
            <span class="et-week-pill">{{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M Y') }}</span>
            <a href="{{ route($personalOnly ? 'employee-todos.my-week' : 'employee-todos.index', array_filter(['week' => $nextWeek, 'employee' => $employeeId])) }}" class="et-btn outline">Next Week →</a>
            <a href="{{ route($personalOnly ? 'employee-todos.my-week' : 'employee-todos.index', array_filter(['week' => now()->startOfWeek()->toDateString(), 'employee' => $employeeId])) }}" class="et-btn outline">This Week</a>
        </div>
    </div>

    @if(session('success'))
    <div style="background:#dcfce7;color:#15803d;padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:13px;font-weight:600;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div style="background:#fee2e2;color:#dc2626;padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:13px;">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
    @endif

    <div class="et-bar">
        @if($canManage)
        <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
            <label style="font-size:12px;font-weight:700;color:#374151;">Employee</label>
            <select name="employee" onchange="this.form.submit()">
                @foreach($allEmployees as $emp)
                <option value="{{ $emp['id'] }}" @selected((string)$employeeId === (string)$emp['id'])>{{ $emp['name'] }}</option>
                @endforeach
            </select>
        </form>
        <form method="POST" action="{{ route('employee-todos.copy-week') }}" onsubmit="return confirm('Copy all tasks from previous week? Status will reset to pending.');">
            @csrf
            <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
            <input type="hidden" name="employee_id" value="{{ $employeeId }}">
            <button type="submit" class="et-btn outline">Copy Previous Week</button>
        </form>
        @else
        <span class="et-emp-badge">{{ $selectedEmp['name'] ?? 'My Week' }}</span>
        @endif
    </div>

    @if($personalOnly)
    <div class="et-hint">
        <strong>Tick each task</strong> when you finish it. Your manager can see your progress on the Weekly Planner.
    </div>
    @endif

    @if($canManage && $employeeId && $selectedEmp)
    <div class="et-emp-banner">
        <div>
            <strong>👤 {{ $selectedEmp['name'] }}</strong>
            <span> — Week of {{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M Y') }}</span>
            @if(($weekStats['total'] ?? 0) > 0)
            <span class="et-admin-stats">· {{ $weekStats['completed'] ?? 0 }}/{{ $weekStats['total'] }} tasks done ({{ $weekStats['percent'] ?? 0 }}%)</span>
            @endif
        </div>
        @if($templates->isNotEmpty())
        <div class="et-assign-actions">
            <button type="button" class="et-btn outline" onclick="openAssignModal()">Load Template</button>
        </div>
        @else
        <div class="et-assign-actions">
            <a href="{{ route('employee-todos.templates.create') }}" class="et-btn outline">Create Template</a>
        </div>
        @endif
    </div>
    @endif

    @if($plan)
        @if($canManage)
        <form method="POST" action="{{ route('employee-todos.plan-notes') }}" class="et-bar">
            @csrf
            <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
            <input type="hidden" name="employee_id" value="{{ $employeeId }}">
            <div class="et-notes">
                <label class="et-label">Week Notes (optional)</label>
                <textarea name="notes" class="et-input" rows="2" placeholder="Goals or reminders for this week…">{{ old('notes', $plan->notes) }}</textarea>
            </div>
            <button type="submit" class="et-btn" style="align-self:flex-end;">Save Notes</button>
        </form>
        @elseif($plan->notes)
        <div class="et-bar" style="font-size:13px;color:#374151;">
            <strong>Week notes from manager:</strong> {{ $plan->notes }}
        </div>
        @endif
    @endif

    @if($personalOnly && ($weekStats['total'] ?? 0) === 0)
    <div class="et-empty">No tasks assigned for this week yet. Check back later or contact your manager.</div>
    @endif

    <div class="et-grid-wrap" @if(($weekStats['total'] ?? 0) === 0 && $personalOnly) style="display:none;" @endif>
        <table class="et-grid">
            <thead>
                <tr>
                    <th class="et-cat-col">Category</th>
                    @foreach($days as $num => $day)
                    <th class="et-day-head">
                        {{ $day['short'] }}
                        <small>{{ $day['date']->format('d M') }}</small>
                        <span class="et-pct {{ ($dayStats[$num]['percent'] ?? 0) >= 100 ? 'done' : '' }}" id="dayPct-{{ $num }}">{{ $dayStats[$num]['percent'] ?? 0 }}%</span>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                <tr>
                    <td class="et-cat-col">
                        <div class="et-cat">
                            <span class="et-cat-dot" style="background:{{ $cat->color }};"></span>
                            {{ $cat->name }}
                        </div>
                    </td>
                    @foreach($days as $num => $day)
                    @php $cellKey = $cat->id.'_'.$num; $cellItems = $items->get($cellKey) ?? collect(); @endphp
                    <td data-cell="{{ $cellKey }}">
                        @foreach($cellItems as $task)
                        <div class="et-task {{ $task->is_completed ? 'done' : '' }}" id="task-{{ $task->id }}" data-id="{{ $task->id }}">
                            <div class="et-task-row">
                                @if($personalOnly)
                                <input type="checkbox" {{ $task->is_completed ? 'checked' : '' }} onchange="toggleTask({{ $task->id }}, this)">
                                @elseif($canManage)
                                <input type="checkbox" {{ $task->is_completed ? 'checked' : '' }} disabled title="Employee marks tasks complete">
                                @endif
                                <div style="flex:1;">
                                    <div class="et-task-title">{{ $task->title }}</div>
                                    <div class="et-task-meta">
                                        @if($task->task_time)<span>🕐 {{ substr($task->task_time, 0, 5) }}</span>@endif
                                        @if($task->checklist_count > 1)<span>☑ {{ $task->checklist_count }}</span>@endif
                                        @if($canManage && $task->is_completed && $task->completed_at)
                                        <span class="et-done-at">✓ Done {{ $task->completed_at->format('d M H:i') }}</span>
                                        @elseif($personalOnly && $task->is_completed)
                                        <span class="et-done-at">✓ Completed</span>
                                        @endif
                                    </div>
                                    @if($canManage)
                                    <button type="button" class="et-task-del" onclick="deleteTask({{ $task->id }})">Remove</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @if($canManage && $employeeId)
                        <button type="button" class="et-add"
                            data-category-id="{{ $cat->id }}"
                            data-day="{{ $num }}"
                            data-category-name="{{ $cat->name }}"
                            data-day-label="{{ $day['label'] }}">+ Add</button>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;padding:30px;color:#9ca3af;">
                    No categories yet. @if($canManage)<a href="{{ route('employee-todos.categories.index') }}">Add categories</a>@endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="et-modal-ov" id="addModal">
    <div class="et-modal">
        <div class="et-modal-head"><h3 id="addModalTitle">Assign Task</h3></div>
        <form id="addForm">
            <div class="et-modal-body">
                <input type="hidden" id="fCategoryId">
                <input type="hidden" id="fDay">
                @if($canManage && $selectedEmp)
                <div class="et-field">
                    <span class="et-label">Employee</span>
                    <div style="font-size:13px;font-weight:700;color:#5b21b6;">{{ $selectedEmp['name'] }}</div>
                </div>
                @endif
                <div class="et-field" id="quickPickFields" style="display:none;">
                    <label class="et-label">Category *</label>
                    <select id="fCategoryPick" class="et-input">
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="et-field" id="quickDayFields" style="display:none;">
                    <label class="et-label">Day *</label>
                    <select id="fDayPick" class="et-input">
                        @foreach($days as $num => $day)
                        <option value="{{ $num }}">{{ $day['label'] }} ({{ $day['date']->format('d M') }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="et-field" id="cellLabelField">
                    <span class="et-label">Category · Day</span>
                    <div id="fCellLabel" style="font-size:13px;font-weight:700;color:#5b21b6;"></div>
                </div>
                <div class="et-field">
                    <label class="et-label">Task *</label>
                    <input type="text" id="fTitle" class="et-input" required maxlength="200" placeholder="What needs to be done?">
                </div>
                <div class="et-field">
                    <label class="et-label">Time (optional)</label>
                    <input type="time" id="fTime" class="et-input">
                </div>
                <div class="et-field">
                    <label class="et-label">Checklist count</label>
                    <input type="number" id="fChecklist" class="et-input" min="1" max="99" value="1">
                </div>
            </div>
            <div class="et-modal-foot">
                <button type="button" class="et-btn outline" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="et-btn" id="addSubmitBtn">Save Task</button>
            </div>
        </form>
    </div>
</div>

@if($canManage && $templates->isNotEmpty())
<div class="et-modal-ov" id="assignModal">
    <div class="et-modal">
        <div class="et-modal-head"><h3>Assign Template</h3></div>
        <div class="et-modal-body">
            @if($selectedEmp)
            <div class="et-field">
                <span class="et-label">Employee</span>
                <div style="font-size:13px;font-weight:700;color:#5b21b6;">{{ $selectedEmp['name'] }}</div>
            </div>
            @endif
            <div class="et-field">
                <label class="et-label">Template</label>
                <select id="assignTemplateId" class="et-input">
                    @foreach($templates as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="et-field" id="assignModeField" style="{{ $hasItems ? '' : 'display:none;' }}">
                <label class="et-label">This week already has tasks</label>
                <select id="assignMode" class="et-input">
                    <option value="merge">Merge — add template tasks (skip duplicates)</option>
                    <option value="replace">Replace all — remove existing tasks first</option>
                </select>
            </div>
        </div>
        <div class="et-modal-foot">
            <button type="button" class="et-btn outline" onclick="closeModal('assignModal')">Cancel</button>
            <button type="button" class="et-btn" onclick="doAssign()">Assign</button>
        </div>
    </div>
</div>
@endif

<div class="et-toast" id="etToast"></div>
@endsection

@section('javascript')
<script>
(function(){
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
const WEEK = @json($weekStart->toDateString());
const EMPLOYEE_ID = @json((int) $employeeId);
const HAS_ITEMS = @json($hasItems);
const EMPLOYEE_NAME = @json($selectedEmp ? ($selectedEmp['name'] ?? 'Employee') : 'Employee');
const IS_EMPLOYEE_VIEW = @json($personalOnly);
const CAN_MANAGE = @json($canManage);

function toast(msg){
    const t = document.getElementById('etToast');
    if(!t) return;
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
}

window.closeModal = function(id){
    document.getElementById(id)?.classList.remove('show');
};

function updateStats(stats){
    if(!stats) return;
    const wp = document.getElementById('weekPct');
    if(wp && stats.week){ wp.textContent = stats.week.percent + '%'; wp.classList.toggle('done', stats.week.percent >= 100); }
    const wc = document.getElementById('weekCount');
    if(wc && stats.week){ wc.textContent = stats.week.completed + '/' + stats.week.total + ' done'; }
    if(stats.days){
        Object.keys(stats.days).forEach(d => {
            const el = document.getElementById('dayPct-'+d);
            if(el){ el.textContent = stats.days[d].percent + '%'; el.classList.toggle('done', stats.days[d].percent >= 100); }
        });
    }
}

window.toggleTask = async function(id, checkbox){
    checkbox.disabled = true;
    try {
        const r = await fetch(`/employee-todos/items/${id}/toggle`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        });
        const d = await r.json();
        if(d.success){
            const el = document.getElementById('task-'+id);
            if(el){
                el.classList.toggle('done', d.item.is_completed);
                const meta = el.querySelector('.et-task-meta');
                if(meta){
                    let doneEl = meta.querySelector('.et-done-at');
                    if(d.item.is_completed){
                        const label = IS_EMPLOYEE_VIEW ? '✓ Completed' : ('✓ Done ' + (d.item.completed_at || ''));
                        if(doneEl) doneEl.textContent = label;
                        else { doneEl = document.createElement('span'); doneEl.className='et-done-at'; doneEl.textContent=label; meta.appendChild(doneEl); }
                    } else if(doneEl) doneEl.remove();
                }
            }
            updateStats(d.stats);
            if(IS_EMPLOYEE_VIEW) toast(d.item.is_completed ? 'Task marked done' : 'Task marked pending');
        } else { checkbox.checked = !checkbox.checked; toast('Update failed'); }
    } catch(e){ checkbox.checked = !checkbox.checked; toast('Update failed'); }
    finally { checkbox.disabled = false; }
};

window.deleteTask = async function(id){
    if(!confirm('Remove this task?')) return;
    const r = await fetch(`/employee-todos/items/${id}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
    });
    const d = await r.json();
    if(d.success){ document.getElementById('task-'+id)?.remove(); updateStats(d.stats); }
    else toast('Delete failed');
};

function taskHtml(item){
    const time = item.task_time ? `<span>🕐 ${String(item.task_time).substring(0,5)}</span>` : '';
    const chk = item.checklist_count > 1 ? `<span>☑ ${item.checklist_count}</span>` : '';
    const done = item.is_completed && item.completed_at ? `<span class="et-done-at">✓ Done ${item.completed_at}</span>` : '';
    const del = CAN_MANAGE ? `<button type="button" class="et-task-del" onclick="deleteTask(${item.id})">Remove</button>` : '';
    const cb = IS_EMPLOYEE_VIEW
        ? `<input type="checkbox" onchange="toggleTask(${item.id}, this)">`
        : (CAN_MANAGE ? `<input type="checkbox" disabled title="Employee marks tasks complete">` : '');
    return `<div class="et-task" id="task-${item.id}" data-id="${item.id}">
        <div class="et-task-row">
            ${cb}
            <div style="flex:1;">
                <div class="et-task-title">${escapeHtml(item.title)}</div>
                <div class="et-task-meta">${time}${chk}${done}</div>
                ${del}
            </div>
        </div>
    </div>`;
}

function escapeHtml(str){
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function saveTask(payload, cellEl){
    if(!EMPLOYEE_ID){ toast('Select an employee first'); return false; }
    if(!payload.title){ toast('Task title is required'); return false; }
    const r = await fetch(@json(route('employee-todos.items.store')), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ week: WEEK, employee_id: EMPLOYEE_ID, ...payload })
    });
    let d;
    try { d = await r.json(); } catch(e){ toast('Could not save task'); return false; }
    if(!r.ok || !d.success){
        const msg = d.message || (d.errors && Object.values(d.errors)[0]?.[0]) || 'Could not save task';
        toast(msg);
        return false;
    }
    if(cellEl && d.item){
        const addBtn = cellEl.querySelector('.et-add');
        if(!cellEl.querySelector('#task-'+d.item.id)){
            const wrap = document.createElement('div');
            wrap.innerHTML = taskHtml(d.item);
            cellEl.insertBefore(wrap.firstElementChild, addBtn);
        }
    }
    updateStats(d.stats);
    if (d.whatsapp?.success) toast('Task saved — WhatsApp sent to ' + EMPLOYEE_NAME);
    else if (d.whatsapp?.message) toast('Task saved — ' + d.whatsapp.message);
    else toast('Task saved');
    return true;
}

function setAddModalMode(mode){
    const isCell = mode === 'cell';
    const isQuick = mode === 'quick';
    const qf = document.getElementById('quickPickFields');
    const qd = document.getElementById('quickDayFields');
    const cf = document.getElementById('cellLabelField');
    const title = document.getElementById('addModalTitle');
    if(qf) qf.style.display = isQuick ? '' : 'none';
    if(qd) qd.style.display = isQuick ? '' : 'none';
    if(cf) cf.style.display = isCell ? '' : 'none';
    if(title){
        if(isQuick) title.textContent = 'Add Task — ' + EMPLOYEE_NAME;
        else if(isCell) title.textContent = 'Add Task — ' + EMPLOYEE_NAME;
        else title.textContent = 'Add Task';
    }
}

window.openCellAssign = function(catId, day, catName, dayLabel){
    setAddModalMode('cell');
    const modal = document.getElementById('addModal');
    if(!modal){ toast('Form not loaded — refresh the page'); return; }
    document.getElementById('fCategoryId').value = catId;
    document.getElementById('fDay').value = day;
    document.getElementById('fCellLabel').textContent = catName + ' · ' + dayLabel;
    document.getElementById('fTitle').value = '';
    document.getElementById('fTime').value = '';
    document.getElementById('fChecklist').value = '1';
    modal.classList.add('show');
    setTimeout(() => document.getElementById('fTitle')?.focus(), 50);
};

window.openQuickAssign = function(){
    setAddModalMode('quick');
    document.getElementById('addModal')?.classList.add('show');
    document.getElementById('fTitle').value = '';
    document.getElementById('fTime').value = '';
    document.getElementById('fChecklist').value = '1';
};

window.openAssignModal = function(){
    document.getElementById('assignModal')?.classList.add('show');
};

window.doAssign = async function(){
    const templateId = document.getElementById('assignTemplateId')?.value;
    const mode = HAS_ITEMS ? document.getElementById('assignMode')?.value : 'merge';
    const r = await fetch(@json(route('employee-todos.assign-template')), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ week: WEEK, employee_id: EMPLOYEE_ID, template_id: parseInt(templateId,10), mode })
    });
    const d = await r.json();
    if(d.success && d.redirect) location.href = d.redirect;
    else if(d.success) toast(d.whatsapp?.success ? 'Template loaded — WhatsApp sent' : (d.whatsapp?.message || 'Template loaded'));
    else toast(d.message || 'Failed');
};

document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.et-modal-ov').forEach(el => document.body.appendChild(el));
    const toastEl = document.getElementById('etToast');
    if(toastEl) document.body.appendChild(toastEl);

    document.addEventListener('click', function(e){
        const btn = e.target.closest('.et-add');
        if(!btn) return;
        e.preventDefault();
        window.openCellAssign(
            parseInt(btn.dataset.categoryId, 10),
            parseInt(btn.dataset.day, 10),
            btn.dataset.categoryName || '',
            btn.dataset.dayLabel || ''
        );
    });

    const addForm = document.getElementById('addForm');
    if(addForm){
        addForm.addEventListener('submit', async function(e){
            e.preventDefault();
            const isQuick = document.getElementById('quickPickFields')?.style.display !== 'none';
            const categoryId = isQuick
                ? parseInt(document.getElementById('fCategoryPick').value, 10)
                : parseInt(document.getElementById('fCategoryId').value, 10);
            const dayOfWeek = isQuick
                ? parseInt(document.getElementById('fDayPick').value, 10)
                : parseInt(document.getElementById('fDay').value, 10);
            const payload = {
                category_id: categoryId,
                day_of_week: dayOfWeek,
                title: document.getElementById('fTitle').value.trim(),
                task_time: document.getElementById('fTime').value || null,
                checklist_count: parseInt(document.getElementById('fChecklist').value, 10) || 1,
            };
            const cell = document.querySelector(`td[data-cell="${categoryId}_${dayOfWeek}"]`);
            const btn = document.getElementById('addSubmitBtn');
            if(btn) btn.disabled = true;
            const ok = await saveTask(payload, cell);
            if(btn) btn.disabled = false;
            if(ok){
                closeModal('addModal');
                document.getElementById('fTitle').value = '';
                document.getElementById('fTime').value = '';
                document.getElementById('fChecklist').value = '1';
            }
        });
    }
});

// Admin: refresh task states every 45s when viewing an employee
if(CAN_MANAGE && EMPLOYEE_ID){
    setInterval(() => { if(!document.hidden) location.reload(); }, 45000);
}
})();
</script>
@endsection
