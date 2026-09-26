@extends('layouts.app')
@section('title', $personalOnly ? 'My To-Do' : 'Weekly To-Do')

@section('css')
<style>
.et-page { padding: 0 20px 60px; max-width: 1400px; margin: 0 auto; }
.et-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin: 20px 0 16px; }
.et-title { font-size: 20px; font-weight: 800; color: #1e1b4b; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.et-title > i { color: #7c5cfc; }
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
.et-cat-name { flex: 1; min-width: 0; }
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
.et-add-row-bar{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px 16px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:14px}
.et-add-row-bar select{min-width:200px;border:1px solid #d1d5db;border-radius:8px;padding:8px 12px;font-size:13px;background:#fff;color:#111827}
.et-row-remove{border:none;background:none;color:#9ca3af;font-size:12px;cursor:pointer;margin-left:auto;padding:2px 6px}
.et-row-remove:hover{color:#dc2626}
.et-grid-empty{padding:28px;text-align:center;color:#9ca3af;font-size:13px}
.et-empty{padding:40px;text-align:center;color:#9ca3af;background:#fff;border:1px dashed #e5e7eb;border-radius:14px;margin-top:12px}
.et-status-strip{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:14px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px 16px}
.et-status-chip{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:800;padding:8px 14px;border-radius:999px}
.et-status-chip.green{background:#dcfce7;color:#15803d}
.et-status-chip.yellow{background:#fef9c3;color:#a16207}
.et-status-chip.red{background:#fee2e2;color:#dc2626}
.et-status-dot{width:10px;height:10px;border-radius:50%;background:currentColor}
.et-badge-pills{display:flex;flex-wrap:wrap;gap:8px}
.et-badge-pill{font-size:11px;font-weight:800;padding:6px 10px;border-radius:999px;background:#f3f4f6;color:#374151}
.et-badge-pill.star{background:#fef3c7;color:#b45309}
.et-badge-pill.super{background:#dcfce7;color:#15803d}
.et-badge-pill.great{background:#fef9c3;color:#a16207}
.et-section{margin-bottom:16px}
.et-section-title{font-size:13px;font-weight:800;color:#1e1b4b;margin:0 0 8px;display:flex;align-items:center;gap:8px}
.et-section-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:10px}
.et-task.status-overdue{background:#fef2f2;border-color:#fca5a5}
.et-task.status-in_progress{background:#eff6ff;border-color:#93c5fd}
.et-task.status-completed.tier-super{background:#f0fdf4;border-color:#86efac}
.et-task.status-completed.tier-great{background:#fefce8;border-color:#fde047}
.et-task-actions{display:flex;gap:6px;margin-top:6px;flex-wrap:wrap}
.et-btn-sm{padding:5px 10px;font-size:11px;border-radius:7px;border:none;font-weight:700;cursor:pointer}
.et-btn-start{background:#2563eb;color:#fff}
.et-btn-end{background:#15803d;color:#fff}
.et-btn-sm:disabled{opacity:.5;cursor:default}
.et-alloc{color:#5b21b6;font-weight:700}
.et-celeb{text-align:center;padding:28px 20px}
.et-celeb-icon{font-size:48px;margin-bottom:10px;animation:etPop .6s ease}
.et-celeb h3{margin:0 0 8px;font-size:20px;font-weight:800;color:#1e1b4b}
.et-celeb p{margin:0;color:#6b7280;font-size:14px}
@keyframes etPop{0%{transform:scale(.4);opacity:0}60%{transform:scale(1.15)}100%{transform:scale(1);opacity:1}}
.et-time-row{display:flex;gap:8px}
.et-time-row .et-field{flex:1}
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
            <i class="fas fa-check-circle"></i> My Weekly Tasks
            @else
            <i class="fas fa-clipboard-list"></i> Weekly To-Do
            @endif
            <span class="et-pct {{ ($weekStats['percent'] ?? 0) >= 100 ? 'done' : '' }}" id="weekPct">{{ $weekStats['percent'] ?? 0 }}%</span>
            @if($canManage && ($weekStats['total'] ?? 0) > 0)
            <span class="et-admin-stats" id="weekCount">{{ $weekStats['completed'] ?? 0 }}/{{ $weekStats['total'] ?? 0 }} done</span>
            @endif
        </div>
        <div class="et-week-nav">
            <a href="{{ route($personalOnly ? 'employee-todos.my-week' : 'employee-todos.index', array_filter(['week' => $prevWeek, 'employee' => $employeeId])) }}" class="et-btn outline"><i class="fas fa-chevron-left"></i> Prev Week</a>
            <span class="et-week-pill">{{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M Y') }}</span>
            <a href="{{ route($personalOnly ? 'employee-todos.my-week' : 'employee-todos.index', array_filter(['week' => $nextWeek, 'employee' => $employeeId])) }}" class="et-btn outline">Next Week <i class="fas fa-chevron-right"></i></a>
            <a href="{{ route($personalOnly ? 'employee-todos.my-week' : 'employee-todos.index', array_filter(['week' => now()->startOfWeek()->toDateString(), 'employee' => $employeeId])) }}" class="et-btn outline"><i class="fas fa-calendar-week"></i> This Week</a>
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
            <button type="submit" class="et-btn outline"><i class="fas fa-copy"></i> Copy Previous Week</button>
        </form>
        @else
        <span class="et-emp-badge">{{ $selectedEmp['name'] ?? 'My Week' }}</span>
        @endif
    </div>

    @if($personalOnly)
    <div class="et-status-strip" id="etStatusStrip">
        <div class="et-status-chip {{ $myStatus['color'] ?? 'green' }}" id="myStatusChip">
            <span class="et-status-dot"></span>
            My Status: <span id="myStatusLabel">{{ $myStatus['label'] ?? 'On Track' }}</span>
        </div>
        <div class="et-badge-pills" id="badgePills">
            <span class="et-badge-pill star"><i class="fas fa-star"></i> Stars <strong id="badgeStars">{{ $badgeStats['stars'] ?? 0 }}</strong></span>
            <span class="et-badge-pill super">Super <strong id="badgeSuper">{{ $badgeStats['super'] ?? 0 }}</strong></span>
            <span class="et-badge-pill great">Great <strong id="badgeGreat">{{ $badgeStats['great'] ?? 0 }}</strong></span>
        </div>
    </div>
    <div class="et-hint">
        <strong>Start</strong> when you begin a task, then press <strong>End</strong> when finished. Finish under half the time for Super Performer; finish early on a future day for a Star.
    </div>

    @if(($todayItems ?? collect())->isNotEmpty() || ($overdueItems ?? collect())->isNotEmpty())
    @if(($overdueItems ?? collect())->isNotEmpty())
    <div class="et-section">
        <h3 class="et-section-title" style="color:#dc2626;"><i class="fas fa-exclamation-circle"></i> Overdue</h3>
        <div class="et-section-cards">
            @foreach($overdueItems as $task)
                @include('employee-todos.partials.task-card', ['task' => $task, 'personalOnly' => true, 'canManage' => false, 'sectionPrefix' => 'ov'])
            @endforeach
        </div>
    </div>
    @endif
    @if(($todayItems ?? collect())->isNotEmpty())
    <div class="et-section">
        <h3 class="et-section-title"><i class="fas fa-sun"></i> Today</h3>
        <div class="et-section-cards">
            @foreach($todayItems as $task)
                @include('employee-todos.partials.task-card', ['task' => $task, 'personalOnly' => true, 'canManage' => false, 'sectionPrefix' => 'td'])
            @endforeach
        </div>
    </div>
    @endif
    @endif
    @endif

    @if($canManage && $employeeId && $selectedEmp)
    <div class="et-emp-banner">
        <div>
            <strong><i class="fas fa-user"></i> {{ $selectedEmp['name'] }}</strong>
            <span> — Week of {{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M Y') }}</span>
            @if(($weekStats['total'] ?? 0) > 0)
            <span class="et-admin-stats">· {{ $weekStats['completed'] ?? 0 }}/{{ $weekStats['total'] }} tasks done ({{ $weekStats['percent'] ?? 0 }}%)</span>
            @endif
        </div>
        @if($templates->isNotEmpty())
        <div class="et-assign-actions">
            <button type="button" class="et-btn outline" onclick="openAssignModal()"><i class="fas fa-file-import"></i> Load Template</button>
        </div>
        @else
        <div class="et-assign-actions">
            <a href="{{ route('employee-todos.templates.create') }}" class="et-btn outline"><i class="fas fa-plus"></i> Create Template</a>
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

    @if($canManage && $employeeId)
    <div class="et-add-row-bar">
        <label style="font-size:12px;font-weight:700;color:#374151;"><i class="fas fa-tags"></i> Category</label>
        <select id="addRowCategory">
            <option value="">Select category…</option>
            @foreach($allCategories as $cat)
            <option value="{{ $cat->id }}"
                data-name="{{ $cat->name }}"
                data-color="{{ $cat->color }}"
                @if($categories->contains('id', $cat->id)) disabled @endif>
                {{ $cat->name }}@if($categories->contains('id', $cat->id)) (added)@endif
            </option>
            @endforeach
        </select>
        <button type="button" class="et-btn" id="addCategoryRowBtn" onclick="addCategoryRow()">
            <i class="fas fa-plus"></i> Add Row
        </button>
        <span style="font-size:12px;color:#6b7280;">Select a category, then add a row and assign tasks to days.</span>
        @if($allCategories->isEmpty())
        <a href="{{ route('employee-todos.categories.index') }}" class="et-btn outline" style="margin-left:auto;">Create Categories</a>
        @endif
    </div>
    @endif

    <div class="et-grid-wrap" id="etGridWrap" @if(($weekStats['total'] ?? 0) === 0 && $personalOnly) style="display:none;" @endif>
        <table class="et-grid" id="etGrid">
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
            <tbody id="etGridBody">
                @forelse($categories as $cat)
                <tr data-category-id="{{ $cat->id }}">
                    <td class="et-cat-col">
                        <div class="et-cat">
                            <span class="et-cat-dot" style="background:{{ $cat->color }};"></span>
                            <span class="et-cat-name">{{ $cat->name }}</span>
                            @if($canManage)
                            <button type="button" class="et-row-remove" title="Hide empty category row" onclick="removeCategoryRow(this)" style="display:none;"><i class="fas fa-times"></i></button>
                            @endif
                        </div>
                    </td>
                    @foreach($days as $num => $day)
                    @php $cellKey = $cat->id.'_'.$num; $cellItems = $items->get($cellKey) ?? collect(); @endphp
                    <td data-cell="{{ $cellKey }}">
                        @foreach($cellItems as $task)
                            @include('employee-todos.partials.task-card', ['task' => $task, 'personalOnly' => $personalOnly, 'canManage' => $canManage])
                        @endforeach
                        @if($canManage && $employeeId)
                        <button type="button" class="et-add"
                            data-category-id="{{ $cat->id }}"
                            data-day="{{ $num }}"
                            data-category-name="{{ $cat->name }}"
                            data-day-label="{{ $day['label'] }}"><i class="fas fa-plus"></i> Add</button>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @empty
                <tr id="etEmptyRow">
                    <td colspan="{{ 1 + count($days) }}" class="et-grid-empty">
                        @if($canManage)
                            No category rows yet. Select a category above and click <strong>Add Row</strong>, then assign tasks to days.
                            @if(($allCategories ?? collect())->isEmpty())
                            <div style="margin-top:8px;"><a href="{{ route('employee-todos.categories.index') }}">Create categories first</a></div>
                            @endif
                        @else
                            No tasks assigned for this week.
                        @endif
                    </td>
                </tr>
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
                        @foreach($allCategories as $cat)
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
                <div class="et-time-row">
                    <div class="et-field">
                        <label class="et-label">Hours *</label>
                        <input type="number" id="fHours" class="et-input" min="0" max="99" value="1">
                    </div>
                    <div class="et-field">
                        <label class="et-label">Minutes *</label>
                        <input type="number" id="fMinutes" class="et-input" min="0" max="59" value="0">
                    </div>
                </div>
                <div style="font-size:11px;color:#6b7280;margin-top:-6px;margin-bottom:10px;">Allocated time budget for this task (required).</div>
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

<div class="et-modal-ov" id="celebModal">
    <div class="et-modal" style="max-width:380px;">
        <div class="et-celeb">
            <div class="et-celeb-icon" id="celebIcon">🎉</div>
            <h3 id="celebTitle">Well done!</h3>
            <p id="celebMessage"></p>
            <button type="button" class="et-btn" style="margin-top:18px;" onclick="closeModal('celebModal')">Awesome</button>
        </div>
    </div>
</div>
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
const DAYS_META = @json($daysMeta ?? []);

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

function markCategoryUsed(catId){
    const opt = document.querySelector('#addRowCategory option[value="'+catId+'"]');
    if(!opt) return;
    opt.disabled = true;
    if(!/\(added\)$/.test(opt.textContent)) opt.textContent = opt.textContent + ' (added)';
}

function unmarkCategoryUsed(catId){
    const opt = document.querySelector('#addRowCategory option[value="'+catId+'"]');
    if(!opt) return;
    opt.disabled = false;
    opt.textContent = (opt.dataset.name || opt.textContent.replace(/\s*\(added\)$/, ''));
}

window.addCategoryRow = function(){
    if(!CAN_MANAGE || !EMPLOYEE_ID){ toast('Select an employee first'); return; }
    const sel = document.getElementById('addRowCategory');
    if(!sel || !sel.value){ toast('Select a category first'); return; }
    const catId = parseInt(sel.value, 10);
    if(document.querySelector('#etGridBody tr[data-category-id="'+catId+'"]')){
        toast('Category row already added');
        return;
    }
    const name = sel.options[sel.selectedIndex].dataset.name || sel.options[sel.selectedIndex].text;
    const color = sel.options[sel.selectedIndex].dataset.color || '#7c5cfc';

    document.getElementById('etEmptyRow')?.remove();

    let cells = '';
    DAYS_META.forEach(day => {
        cells += `<td data-cell="${catId}_${day.num}">
            <button type="button" class="et-add"
                data-category-id="${catId}"
                data-day="${day.num}"
                data-category-name="${escapeHtml(name)}"
                data-day-label="${escapeHtml(day.label)}"><i class="fas fa-plus"></i> Add</button>
        </td>`;
    });

    const tr = document.createElement('tr');
    tr.dataset.categoryId = String(catId);
    tr.innerHTML = `<td class="et-cat-col">
        <div class="et-cat">
            <span class="et-cat-dot" style="background:${escapeHtml(color)};"></span>
            <span class="et-cat-name">${escapeHtml(name)}</span>
            <button type="button" class="et-row-remove" title="Remove empty row" onclick="removeCategoryRow(this)"><i class="fas fa-times"></i></button>
        </div>
    </td>${cells}`;
    document.getElementById('etGridBody').appendChild(tr);
    markCategoryUsed(catId);
    sel.value = '';
    toast('Category row added — assign tasks to days');
};

window.removeCategoryRow = function(btn){
    const tr = btn.closest('tr');
    if(!tr) return;
    if(tr.querySelector('.et-task')){
        toast('Remove all tasks in this category first, or keep the row');
        return;
    }
    const catId = tr.dataset.categoryId;
    tr.remove();
    if(catId) unmarkCategoryUsed(catId);
    const body = document.getElementById('etGridBody');
    if(body && !body.querySelector('tr[data-category-id]')){
        const empty = document.createElement('tr');
        empty.id = 'etEmptyRow';
        empty.innerHTML = `<td colspan="${1 + DAYS_META.length}" class="et-grid-empty">No category rows yet. Select a category above and click <strong>Add Row</strong>, then assign tasks to days.</td>`;
        body.appendChild(empty);
    }
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
    if(stats.badges){
        const s = document.getElementById('badgeStars'); if(s) s.textContent = stats.badges.stars;
        const su = document.getElementById('badgeSuper'); if(su) su.textContent = stats.badges.super;
        const g = document.getElementById('badgeGreat'); if(g) g.textContent = stats.badges.great;
    }
    if(stats.status){
        const chip = document.getElementById('myStatusChip');
        const label = document.getElementById('myStatusLabel');
        if(label) label.textContent = stats.status.label;
        if(chip){
            chip.classList.remove('green','yellow','red');
            chip.classList.add(stats.status.color || 'green');
        }
    }
}

function showCelebration(popup){
    if(!popup) return;
    const icons = { early_start: '⚡', star_super: '⭐', super: '🏆', great: '👍' };
    document.getElementById('celebIcon').textContent = icons[popup.type] || '🎉';
    document.getElementById('celebTitle').textContent = popup.title || 'Well done!';
    document.getElementById('celebMessage').textContent = popup.message || '';
    document.getElementById('celebModal')?.classList.add('show');
}

function applyItemToDom(item){
    document.querySelectorAll(`[data-id="${item.id}"]`).forEach(el => {
        el.className = 'et-task status-' + (item.status || 'pending')
            + (item.is_completed ? ' done' : '')
            + (item.performance_tier ? ' tier-' + item.performance_tier : '');
        const meta = el.querySelector('.et-task-meta');
        const actions = el.querySelector('.et-task-actions');
        if(meta){
            const alloc = item.allocated_minutes || 60;
            const h = Math.floor(alloc/60), m = alloc%60;
            const allocLabel = h && m ? `${h}h ${m}m` : (h ? `${h}h` : `${m}m`);
            let html = `<span class="et-alloc"><i class="far fa-hourglass"></i> ${allocLabel}</span>`;
            if(item.task_time) html += `<span><i class="far fa-clock"></i> ${String(item.task_time).substring(0,5)}</span>`;
            if(item.checklist_count > 1) html += `<span><i class="far fa-check-square"></i> ${item.checklist_count}</span>`;
            if(item.started_at && !item.ended_at) html += `<span><i class="fas fa-play"></i> Started</span>`;
            if(item.is_completed){
                if(item.earned_star) html += `<span class="et-badge-pill star" style="padding:2px 6px;"><i class="fas fa-star"></i></span>`;
                if(item.performance_tier === 'super') html += `<span class="et-badge-pill super" style="padding:2px 6px;">Super</span>`;
                if(item.performance_tier === 'great') html += `<span class="et-badge-pill great" style="padding:2px 6px;">Great</span>`;
                html += `<span class="et-done-at"><i class="fas fa-check"></i> ${item.ended_at || item.completed_at || 'Done'}</span>`;
            } else if(item.status === 'overdue'){
                html += `<span style="color:#dc2626;font-weight:700;">Overdue</span>`;
            }
            meta.innerHTML = html;
        }
        if(actions && IS_EMPLOYEE_VIEW){
            if(!item.is_completed && !item.started_at){
                actions.innerHTML = `<button type="button" class="et-btn-sm et-btn-start" onclick="startTask(${item.id})"><i class="fas fa-play"></i> Start</button>`;
            } else if(!item.is_completed && item.started_at){
                actions.innerHTML = `<button type="button" class="et-btn-sm et-btn-end" onclick="endTask(${item.id})"><i class="fas fa-stop"></i> End</button>`;
            } else {
                actions.innerHTML = '';
            }
        }
    });
}

window.startTask = async function(id){
    try {
        const r = await fetch(`/employee-todos/items/${id}/start`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        });
        const d = await r.json();
        if(d.success && d.item){
            applyItemToDom(d.item);
            updateStats(d.stats);
            if(d.popup) showCelebration(d.popup);
            else toast('Task started');
        } else toast(d.message || 'Could not start');
    } catch(e){ toast('Could not start'); }
};

window.endTask = async function(id){
    try {
        const r = await fetch(`/employee-todos/items/${id}/end`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        });
        const d = await r.json();
        if(d.success && d.item){
            applyItemToDom(d.item);
            updateStats(d.stats);
            if(d.popup) showCelebration(d.popup);
            else toast('Task completed');
        } else toast(d.message || 'Could not end');
    } catch(e){ toast('Could not end'); }
};

window.toggleTask = async function(id, checkbox){
    checkbox.disabled = true;
    try {
        const r = await fetch(`/employee-todos/items/${id}/toggle`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        });
        const d = await r.json();
        if(d.success && d.item){
            applyItemToDom(d.item);
            updateStats(d.stats);
            toast('Task reset to pending');
        } else {
            checkbox.checked = !checkbox.checked;
            toast(d.message || 'Update failed');
        }
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
    if(d.success){
        document.querySelectorAll(`[data-id="${id}"]`).forEach(el => el.remove());
        updateStats(d.stats);
    }
    else toast('Delete failed');
};

function taskHtml(item){
    const alloc = item.allocated_minutes || 60;
    const h = Math.floor(alloc/60), m = alloc%60;
    const allocLabel = h && m ? `${h}h ${m}m` : (h ? `${h}h` : `${m}m`);
    const time = item.task_time ? `<span><i class="far fa-clock"></i> ${String(item.task_time).substring(0,5)}</span>` : '';
    const chk = item.checklist_count > 1 ? `<span><i class="far fa-check-square"></i> ${item.checklist_count}</span>` : '';
    const del = CAN_MANAGE ? `<button type="button" class="et-task-del" onclick="deleteTask(${item.id})">Remove</button>` : '';
    const cb = CAN_MANAGE ? `<input type="checkbox" disabled title="Employee uses Start / End">` : '';
    const status = item.status || 'pending';
    return `<div class="et-task status-${status}" id="task-${item.id}" data-id="${item.id}">
        <div class="et-task-row">
            ${cb}
            <div style="flex:1;">
                <div class="et-task-title">${escapeHtml(item.title)}</div>
                <div class="et-task-meta"><span class="et-alloc"><i class="far fa-hourglass"></i> ${allocLabel}</span>${time}${chk}</div>
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
    const totalMins = (parseInt(payload.allocated_hours,10)||0)*60 + (parseInt(payload.allocated_minutes,10)||0);
    if(totalMins < 1){ toast('Set allocated hours and/or minutes'); return false; }
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
        if(!cellEl.querySelector('[data-id="'+d.item.id+'"]')){
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
    document.getElementById('fHours').value = '1';
    document.getElementById('fMinutes').value = '0';
    modal.classList.add('show');
    setTimeout(() => document.getElementById('fTitle')?.focus(), 50);
};

window.openQuickAssign = function(){
    setAddModalMode('quick');
    document.getElementById('addModal')?.classList.add('show');
    document.getElementById('fTitle').value = '';
    document.getElementById('fTime').value = '';
    document.getElementById('fChecklist').value = '1';
    document.getElementById('fHours').value = '1';
    document.getElementById('fMinutes').value = '0';
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
                allocated_hours: parseInt(document.getElementById('fHours').value, 10) || 0,
                allocated_minutes: parseInt(document.getElementById('fMinutes').value, 10) || 0,
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
                document.getElementById('fHours').value = '1';
                document.getElementById('fMinutes').value = '0';
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
