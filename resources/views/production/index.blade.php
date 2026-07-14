@extends('layouts.app')
@section('title', 'Production Board')

@section('css')
<style>
/* ── Layout ─────────────────────────────────────────────────── */
.prod-page      { padding: 0 20px 60px; width: 100%; max-width: 100%; box-sizing: border-box; }
.prod-header    { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin: 20px 0 18px; }
.prod-title     { font-size: 20px; font-weight: 800; color: #1e1b4b; display: flex; align-items: center; gap: 10px; }
.prod-title svg { width: 26px; height: 26px; flex-shrink: 0; }

/* ── Stats bar ─────────────────────────────────────────────── */
.prod-stats     { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; width: 100%; }
.prod-stat      { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 11px 18px; display: flex; align-items: center; gap: 12px; flex: 1; min-width: 120px; }
.prod-stat-dot  { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.prod-stat-info { display: flex; flex-direction: column; }
.prod-stat-val  { font-size: 18px; font-weight: 800; color: #111827; line-height: 1; }
.prod-stat-lbl  { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; margin-top: 2px; }

/* ── Search & filter bar ────────────────────────────────────── */
.prod-bar       { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px 16px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 22px; width: 100%; box-sizing: border-box; }
.prod-bar input, .prod-bar select { border: 1px solid #d1d5db; border-radius: 8px; padding: 7px 12px; font-size: 13px; color: #374151; background: #f9fafb; outline: none; }
.prod-bar input:focus, .prod-bar select:focus { border-color: #7c5cfc; background: #fff; }
.prod-btn       { background: #7c5cfc; color: #fff; border: none; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
.prod-btn:hover { background: #5b3fd9; color: #fff; text-decoration: none; }
.prod-btn.outline { background: #fff; color: #7c5cfc; border: 1.5px solid #7c5cfc; }
.prod-btn.outline:hover { background: #ede9fe; color: #5b3fd9; }

/* ── Kanban board (pw- prefix avoids jKanban .kanban-board conflict) ── */
.pw-kanban-board   { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 14px; align-items: start; width: 100%; box-sizing: border-box; }
@media (max-width: 1400px) { .pw-kanban-board { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 900px)  { .pw-kanban-board { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 600px)  { .pw-kanban-board { grid-template-columns: 1fr; } }

.pw-kb-col         { background: #f3f4f6; border-radius: 14px; overflow: hidden; min-width: 0; }
.pw-kb-col-head    { padding: 12px 14px 10px; display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid transparent; gap: 8px; }
.pw-kb-col-title   { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; display: flex; align-items: center; gap: 7px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pw-kb-col-badge   { background: rgba(0,0,0,.08); border-radius: 20px; font-size: 11px; font-weight: 700; padding: 2px 9px; min-width: 26px; text-align: center; flex-shrink: 0; }
.pw-kb-col-body    { padding: 10px; display: flex; flex-direction: column; gap: 10px; min-height: 80px; }

/* ── Job card ───────────────────────────────────────────────── */
.pw-kb-card        { background: #fff; border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.06); border: 1px solid #e5e7eb; overflow: hidden; transition: transform .15s, box-shadow .15s; }
.pw-kb-card:hover  { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,.10); }
.pw-kb-card-head   { padding: 2px 12px; height: 4px; }
.pw-kb-card-body   { padding: 10px 12px 8px; }
.pw-kb-job-num     { font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .05em; }
.pw-kb-job-title   { font-size: 13px; font-weight: 700; color: #111827; margin: 2px 0 6px; line-height: 1.3; word-break: break-word; }
.pw-kb-job-cust    { font-size: 12px; color: #6b7280; display: flex; align-items: center; gap: 5px; margin-bottom: 6px; }
.pw-kb-job-meta    { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 7px; align-items: center; }
.pw-kb-tag         { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; letter-spacing: .04em; }
.pw-kb-cost        { font-size: 10px; font-weight: 700; margin-top: 6px; display: flex; flex-wrap: wrap; gap: 5px; }
.pw-kb-cost span   { padding: 2px 7px; border-radius: 10px; }
.pw-kb-due         { font-size: 10px; color: #9ca3af; margin-left: auto; display: flex; align-items: center; gap: 3px; }
.pw-kb-due.overdue { color: #ef4444; font-weight: 700; }
.pw-kb-card-foot   { padding: 8px 12px; border-top: 1px solid #f3f4f6; display: flex; align-items: center; justify-content: space-between; }
.pw-kb-files-info  { font-size: 11px; color: #9ca3af; display: flex; align-items: center; gap: 4px; }
.pw-kb-view-btn    { font-size: 11px; font-weight: 700; color: #7c5cfc; text-decoration: none; padding: 3px 10px; border-radius: 20px; background: #ede9fe; }
.pw-kb-view-btn:hover { background: #ddd6fe; color: #5b3fd9; text-decoration: none; }

/* ── Empty column ───────────────────────────────────────────── */
.pw-kb-empty       { text-align: center; padding: 30px 12px; color: #d1d5db; font-size: 12px; }
.pw-kb-empty svg   { width: 36px; height: 36px; margin: 0 auto 6px; display: block; opacity: .4; }

/* ── Team chips on columns ──────────────────────────────────── */
.pw-kb-team        { padding: 8px 12px 10px; border-bottom: 1px solid #e5e7eb; background: #fafafa; min-height: 38px; }
.pw-kb-team-label  { font-size: 9px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 5px; }
.pw-kb-team-list   { display: flex; flex-wrap: wrap; gap: 5px; }
.pw-kb-emp         { display: inline-flex; align-items: center; gap: 5px; background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; padding: 2px 8px 2px 3px; font-size: 10px; font-weight: 600; color: #374151; }
.pw-kb-emp-av      { width: 20px; height: 20px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 8px; font-weight: 800; color: #fff; flex-shrink: 0; }
.pw-kb-team-empty  { font-size: 10px; color: #d1d5db; font-style: italic; }
</style>
@endsection

@section('content')
<div class="prod-page">

    {{-- Header --}}
    <div class="prod-header">
        <div class="prod-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="#7c5cfc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="18" rx="3"/>
                <path d="M8 3v18M16 3v18M2 9h20M2 15h20"/>
            </svg>
            Production Board
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            @if($isAdmin)
            <a href="{{ route('production.jobs') }}" class="prod-btn outline">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                All Jobs
            </a>
            <a href="{{ route('production.team') }}" class="prod-btn outline">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Team
            </a>
            <a href="{{ route('production.start-job') }}" class="prod-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 5v14M5 12h14"/></svg>
                Start Job
            </a>
            <a href="{{ route('reports.production-report') }}" class="prod-btn outline">Profit / Loss</a>
            @endif
        </div>
    </div>

    {{-- Stats bar --}}
    <div class="prod-stats">
        @foreach($visibleStages as $key)
        @php $label = $stages[$key]; $cnt = isset($grouped[$key]) ? $grouped[$key]->count() : 0; @endphp
        <div class="prod-stat">
            <div class="prod-stat-dot" style="background:{{ \App\ProductionJob::stageColor($key) }}"></div>
            <div class="prod-stat-info">
                <span class="prod-stat-val">{{ $cnt }}</span>
                <span class="prod-stat-lbl">{{ $label }}</span>
            </div>
        </div>
        @endforeach
    </div>

    @if($canViewCosts && ($costSummary['total_cost'] > 0 || $costSummary['total_revenue'] > 0))
    <div class="prod-stats" style="margin-top:4px;">
        <div class="prod-stat" style="border-color:#fde68a;background:#fffbeb;">
            <div class="prod-stat-dot" style="background:#f59e0b;"></div>
            <div class="prod-stat-info">
                <span class="prod-stat-val">Rs {{ number_format($costSummary['total_cost'], 2) }}</span>
                <span class="prod-stat-lbl">Total Cost</span>
            </div>
        </div>
        <div class="prod-stat" style="border-color:#bfdbfe;background:#eff6ff;">
            <div class="prod-stat-dot" style="background:#3b82f6;"></div>
            <div class="prod-stat-info">
                <span class="prod-stat-val">Rs {{ number_format($costSummary['total_revenue'], 2) }}</span>
                <span class="prod-stat-lbl">Revenue</span>
            </div>
        </div>
        <div class="prod-stat" style="border-color:{{ $costSummary['total_profit'] >= 0 ? '#86efac' : '#fca5a5' }};background:{{ $costSummary['total_profit'] >= 0 ? '#f0fdf4' : '#fef2f2' }};">
            <div class="prod-stat-dot" style="background:{{ $costSummary['total_profit'] >= 0 ? '#16a34a' : '#dc2626' }};"></div>
            <div class="prod-stat-info">
                <span class="prod-stat-val" style="color:{{ $costSummary['total_profit'] >= 0 ? '#15803d' : '#dc2626' }};">Rs {{ number_format($costSummary['total_profit'], 2) }}</span>
                <span class="prod-stat-lbl">Profit / Loss</span>
            </div>
        </div>
    </div>
    @endif

    {{-- Search / filter bar --}}
    <form method="GET" action="{{ route('production.index') }}" class="prod-bar">
        <input type="text" name="q" placeholder="Search job, customer, #number…" value="{{ $search }}" style="flex:1;min-width:180px;">
        <select name="stage">
            <option value="all" @selected(! $stageFilter || $stageFilter === 'all')>All Stages</option>
            @foreach($visibleStages as $key)
            <option value="{{ $key }}" @selected($stageFilter === $key)>{{ $stages[$key] }}</option>
            @endforeach
        </select>
        <button type="submit" class="prod-btn">Filter</button>
        @if($search || ($stageFilter && $stageFilter !== 'all'))
        <a href="{{ route('production.index') }}" class="prod-btn outline">Clear</a>
        @endif
    </form>

    {{-- Kanban board --}}
    @php $colCount = max(count($visibleStages), 1); @endphp
    <div class="pw-kanban-board" style="grid-template-columns: repeat({{ $colCount }}, minmax(0, 1fr));">
        @foreach($visibleStages as $stageKey)
        @php
            $stageLabel = $stages[$stageKey];
            $color = \App\ProductionJob::stageColor($stageKey);
            $cards = $grouped[$stageKey] ?? collect();
            $members = $stageEmployees[$stageKey] ?? collect();
        @endphp
        <div class="pw-kb-col">
            <div class="pw-kb-col-head" style="border-bottom-color:{{ $color }};">
                <span class="pw-kb-col-title" style="color:{{ $color }};">
                    @if($stageKey === 'design')
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9M16.5 3.5l4 4L7 21H3v-4L16.5 3.5z"/></svg>
                    @elseif($stageKey === 'printing')
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    @elseif($stageKey === 'production')
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 3l4 4-4 4M2 11h8"/></svg>
                    @elseif($stageKey === 'quality')
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 12l2 2 4-4M12 22C6.5 22 2 17.5 2 12S6.5 2 12 2s10 4.5 10 10-4.5 10-10 10z"/></svg>
                    @elseif($stageKey === 'dispatch')
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v3M9 21h6M12 21v-6M16 8h4l3 5v3h-7V8z"/></svg>
                    @else
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4L12 14.01l-3-3"/></svg>
                    @endif
                    @if(in_array($stageKey, \App\ProductionStageEmployee::workableStages(), true))
                    <a href="{{ route('production.section', $stageKey) }}" style="color:inherit;text-decoration:none;" title="Open section dashboard">{{ $stageLabel }} ↗</a>
                    @else
                    {{ $stageLabel }}
                    @endif
                </span>
                <span class="pw-kb-col-badge" style="color:{{ $color }}">{{ $cards->count() }}</span>
            </div>
            <div class="pw-kb-team">
                <div class="pw-kb-team-label">Team</div>
                <div class="pw-kb-team-list">
                    @forelse($members as $member)
                    @php
                        $u = $member->user;
                        $name = trim(($u->surname ?? '') . ' ' . ($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                        $initials = strtoupper(substr($u->first_name ?? $u->username ?? 'U', 0, 1) . substr($u->last_name ?? '', 0, 1));
                    @endphp
                    <span class="pw-kb-emp" title="{{ $name }} @if($member->whatsapp_number)— {{ $member->whatsapp_number }}@endif">
                        <span class="pw-kb-emp-av" style="background:{{ $color }};">{{ $initials ?: 'U' }}</span>
                        {{ \Illuminate\Support\Str::limit($name ?: $u->username, 14) }}
                    </span>
                    @empty
                    <span class="pw-kb-team-empty">No employees assigned</span>
                    @endforelse
                </div>
            </div>
            <div class="pw-kb-col-body">
                @forelse($cards as $job)
                @php
                    $priColor = \App\ProductionJob::priorityColor($job->priority);
                    $isOverdue = $job->due_date && $job->due_date->isPast() && $stageKey !== 'completed';
                @endphp
                <div class="pw-kb-card">
                    <div class="pw-kb-card-head" style="background:{{ $color }};"></div>
                    <div class="pw-kb-card-body">
                        <div class="pw-kb-job-num">{{ $job->job_number }}</div>
                        <div class="pw-kb-job-title">{{ $job->title }}</div>
                        <div class="pw-kb-job-cust">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
                            {{ $job->customer_name }}
                        </div>
                        <div class="pw-kb-job-meta">
                            <span class="pw-kb-tag" style="background:{{ $priColor }}22;color:{{ $priColor }};">{{ ucfirst($job->priority) }}</span>
                            @if($job->google_drive_url)
                            <span class="pw-kb-tag" style="background:#0ea5e922;color:#0ea5e9;">
                                <svg width="9" height="9" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:2px;"><path d="M6.28 3l5.72 9.9L3 19.5h4.28L12 11l4.72 8.5H21L12 3H6.28z"/></svg>
                                Drive
                            </span>
                            @endif
                            @if($job->files->count())
                            <span class="pw-kb-tag" style="background:#e0e7ff;color:#4f46e5;">📎 {{ $job->files->count() }}</span>
                            @endif
                            @if($job->due_date)
                            <span class="pw-kb-due @if($isOverdue) overdue @endif">
                                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                {{ $job->due_date->format('d M') }}
                            </span>
                            @endif
                        </div>
                        @if($canViewCosts && !empty($jobCosts[$job->id]))
                        @php $jc = $jobCosts[$job->id]; @endphp
                        @if($jc['total_cost'] > 0 || $jc['revenue'] > 0)
                        <div class="pw-kb-cost">
                            @if($jc['total_cost'] > 0)
                            <span style="background:#fef3c7;color:#b45309;">Cost Rs {{ number_format($jc['total_cost'], 0) }}</span>
                            @endif
                            @if($jc['revenue'] > 0)
                            <span style="background:#dbeafe;color:#1d4ed8;">Rev Rs {{ number_format($jc['revenue'], 0) }}</span>
                            @endif
                            @if($jc['revenue'] > 0 || $jc['total_cost'] > 0)
                            <span style="background:{{ $jc['profit'] >= 0 ? '#dcfce7' : '#fee2e2' }};color:{{ $jc['profit'] >= 0 ? '#15803d' : '#dc2626' }};">
                                {{ $jc['profit'] >= 0 ? 'Profit' : 'Loss' }} Rs {{ number_format(abs($jc['profit']), 0) }}
                            </span>
                            @endif
                        </div>
                        @endif
                        @endif
                    </div>
                    <div class="pw-kb-card-foot">
                        <div class="pw-kb-files-info">
                            @if($job->latestStage && $job->latestStage->movedBy)
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
                            {{ $job->latestStage->movedBy->name ?? '—' }}
                            @else
                            {{ $job->creator->name ?? '—' }}
                            @endif
                        </div>
                        <a href="{{ route('production.show', $job) }}" class="pw-kb-view-btn">View →</a>
                    </div>
                </div>
                @empty
                <div class="pw-kb-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M9 9h6M9 13h4"/></svg>
                    No jobs
                </div>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>

</div>
@endsection
