@extends('layouts.app')
@section('title', $job->job_number . ' — ' . $job->title)

@section('css')
@include('production.partials.convert-product-styles')
<style>
/* ── Layout ─────────────────────────────────────────────────── */
.pjob-page      { padding: 0 20px 60px; max-width: 1200px; margin: 0 auto; }
.pjob-back      { display: inline-flex; align-items: center; gap: 6px; color: #7c5cfc; font-size: 13px; font-weight: 600; text-decoration: none; margin: 18px 0 14px; }
.pjob-back:hover{ text-decoration: none; color: #5b3fd9; }

/* ── Hero header ────────────────────────────────────────────── */
@php $jobStageColor = \App\ProductionJob::stageColor($job->current_stage); @endphp
.pjob-hero      {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 55%, {{ $jobStageColor }} 100%);
    border-radius: 18px;
    padding: 28px 30px;
    color: #fff !important;
    margin-bottom: 22px;
    position: relative;
    overflow: hidden;
    box-shadow: inset 0 -3px 0 {{ $jobStageColor }}, 0 4px 20px rgba(15,23,42,.25);
}
.pjob-hero::after { content: ''; position: absolute; right: -40px; top: -40px; width: 200px; height: 200px; border-radius: 50%; background: {{ $jobStageColor }}33; }
.pjob-hero-top  { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-start; justify-content: space-between; position: relative; z-index: 1; }
.pjob-num       { font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: #fff !important; opacity: .75; }
.pjob-title-h   { font-size: 22px; font-weight: 800; margin: 4px 0 10px; color: #fff !important; }
.pjob-cust      { font-size: 14px; color: #fff !important; opacity: .9; display: flex; align-items: center; gap: 7px; }
body.theme-admin-pro #scrollable-container .pjob-hero,
body.theme-admin-pro #scrollable-container .pjob-hero .pjob-num,
body.theme-admin-pro #scrollable-container .pjob-hero .pjob-title-h,
body.theme-admin-pro #scrollable-container .pjob-hero .pjob-cust {
    color: #fff !important;
}
.pjob-actions   { display: flex; gap: 8px; flex-wrap: wrap; position: relative; z-index: 1; }

/* Stage badge */
.pjob-stage-badge { display: inline-flex; align-items: center; gap: 7px; background: rgba(255,255,255,.15); backdrop-filter: blur(6px); border: 1px solid rgba(255,255,255,.2); border-radius: 30px; padding: 6px 16px; font-size: 13px; font-weight: 700; color: #fff; }
.pjob-stage-dot   { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

/* Action buttons */
.pjob-btn       { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 10px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; text-decoration: none; transition: all .15s; }
.pjob-btn-adv   { background: #10b981; color: #fff; }
.pjob-btn-adv:hover { background: #059669; color: #fff; text-decoration: none; }
.pjob-btn-edit  { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.3); }
.pjob-btn-edit:hover { background: rgba(255,255,255,.25); color: #fff; text-decoration: none; }
.pjob-btn-inq   { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.2); font-size: 12px; }
.pjob-btn-inq:hover { background: rgba(255,255,255,.22); color: #fff; text-decoration: none; }

/* Stage pipeline */
.pjob-pipeline  { display: flex; align-items: center; gap: 0; margin: 16px 0 0; flex-wrap: wrap; }
.pjob-pip-step  { display: flex; flex-direction: column; align-items: center; gap: 4px; flex: 1; min-width: 60px; }
.pjob-pip-dot   { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; border: 3px solid rgba(255,255,255,.3); color: rgba(255,255,255,.5); }
.pjob-pip-dot.active   { border-color: #fff; color: #fff; background: rgba(255,255,255,.2); }
.pjob-pip-dot.done     { background: rgba(255,255,255,.9); color: #312e81; border-color: #fff; }
.pjob-pip-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; opacity: .7; text-align: center; }
.pjob-pip-label.active { opacity: 1; }
.pjob-pip-line  { flex: 1; height: 2px; background: rgba(255,255,255,.2); margin-bottom: 16px; min-width: 14px; }
.pjob-pip-line.done { background: rgba(255,255,255,.7); }

/* ── Grid layout ─────────────────────────────────────────────── */
.pjob-grid      { display: grid; grid-template-columns: 1fr 340px; gap: 18px; align-items: start; }
@media (max-width: 900px) { .pjob-grid { grid-template-columns: 1fr; } }

/* ── Cards ───────────────────────────────────────────────────── */
.pjob-card      { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; margin-bottom: 18px; }
.pjob-card-head { padding: 13px 18px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; gap: 9px; }
.pjob-card-head h3 { font-size: 14px; font-weight: 800; color: #111827; margin: 0; }
.pjob-card-body { padding: 16px 18px; }
.pjob-card-icon { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

/* ── Info rows ───────────────────────────────────────────────── */
.pjob-info-row  { display: flex; gap: 8px; padding: 6px 0; border-bottom: 1px dashed #f3f4f6; font-size: 13px; }
.pjob-info-row:last-child { border-bottom: none; }
.pjob-info-label { color: #6b7280; font-weight: 600; min-width: 110px; flex-shrink: 0; }
.pjob-info-val  { color: #111827; word-break: break-word; }
.pjob-info-val a { color: #7c5cfc; text-decoration: none; }
.pjob-info-val a:hover { text-decoration: underline; }

/* ── File list ───────────────────────────────────────────────── */
.pjob-file-item { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 9px; background: #f9fafb; border: 1px solid #e5e7eb; margin-bottom: 7px; }
.pjob-file-icon { font-size: 20px; flex-shrink: 0; }
.pjob-file-name { font-size: 12px; font-weight: 700; color: #111827; flex: 1; word-break: break-all; }
.pjob-file-meta { font-size: 10px; color: #9ca3af; }
.pjob-file-actions { display: flex; gap: 5px; }
.pjob-file-btn  { padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 700; border: none; cursor: pointer; }
.pjob-file-dl   { background: #ede9fe; color: #7c5cfc; text-decoration: none; }
.pjob-file-dl:hover { background: #ddd6fe; text-decoration: none; }
.pjob-file-del  { background: #fee2e2; color: #ef4444; }
.pjob-file-del:hover { background: #fecaca; }

/* ── Upload area ─────────────────────────────────────────────── */
.pjob-upload-area { border: 2px dashed #d1d5db; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: border-color .15s; }
.pjob-upload-area:hover, .pjob-upload-area.dragover { border-color: #7c5cfc; background: #f5f3ff; }
.pjob-upload-area p { font-size: 12px; color: #9ca3af; margin: 6px 0 0; }

/* ── Timeline ─────────────────────────────────────────────────── */
.pjob-timeline  { position: relative; padding-left: 28px; }
.pjob-timeline::before { content: ''; position: absolute; left: 9px; top: 6px; bottom: 0; width: 2px; background: #e5e7eb; }
.pjob-tl-item   { position: relative; margin-bottom: 18px; }
.pjob-tl-dot    { position: absolute; left: -22px; top: 3px; width: 14px; height: 14px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 0 2px #e5e7eb; }
.pjob-tl-meta   { font-size: 11px; color: #9ca3af; margin-bottom: 2px; }
.pjob-tl-body   { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 9px; padding: 8px 12px; }
.pjob-tl-stage  { font-size: 12px; font-weight: 800; }
.pjob-tl-notes  { font-size: 12px; color: #374151; margin-top: 3px; }

/* ── Drive link ─────────────────────────────────────────────────── */
.pjob-drive-row { display: flex; gap: 8px; align-items: center; }
.pjob-drive-input { flex: 1; border: 1px solid #d1d5db; border-radius: 8px; padding: 7px 10px; font-size: 12px; color: #374151; outline: none; }
.pjob-drive-input:focus { border-color: #7c5cfc; }
.pjob-drive-btn { background: #7c5cfc; color: #fff; border: none; border-radius: 8px; padding: 7px 14px; font-size: 12px; font-weight: 700; cursor: pointer; flex-shrink: 0; }
.pjob-drive-btn:hover { background: #5b3fd9; }

/* ── Success toast ───────────────────────────────────────────── */
.prod-toast     { position: fixed; bottom: 28px; right: 24px; background: #111827; color: #fff; border-radius: 12px; padding: 12px 20px; font-size: 13px; font-weight: 600; box-shadow: 0 8px 24px rgba(0,0,0,.2); z-index: 9999; display: none; }
.prod-toast.show { display: block; animation: toastIn .3s ease; }
@keyframes toastIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }

/* ── Task status card ────────────────────────────────────────── */
.pjob-task-card  { border-radius: 12px; padding: 14px 16px; margin-bottom: 8px; }
.pjob-task-idle  { background: #f9fafb; border: 1.5px dashed #d1d5db; }
.pjob-task-active{ background: #fffbeb; border: 1.5px solid #f59e0b; }
.pjob-task-done  { background: #f0fdf4; border: 1.5px solid #22c55e; }
.pjob-task-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; margin-bottom: 6px; }
.pjob-task-row   { display: flex; align-items: center; gap: 8px; }
.pjob-task-time  { font-size: 12px; color: #374151; flex: 1; }
.task-btn        { border: none; border-radius: 9px; padding: 7px 16px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; }
.task-btn-start  { background: #7c5cfc; color: #fff; }
.task-btn-start:hover { background: #5b3fd9; }
.task-btn-end    { background: #10b981; color: #fff; }
.task-btn-end:hover { background: #059669; }

/* ── Materials ────────────────────────────────────────────────── */
.pjob-mat-search { width: 100%; border: 1px solid #d1d5db; border-radius: 9px; padding: 9px 12px; font-size: 13px; box-sizing: border-box; outline: none; background: #fff; color: #111827; }
.pjob-mat-search:focus { border-color: #7c5cfc; box-shadow: 0 0 0 3px rgba(124, 92, 252, 0.12); }
.pjob-mat-qty-input { width: 80px; border: 1px solid #d1d5db; border-radius: 8px; padding: 7px 10px; font-size: 13px; text-align: center; outline: none; background: #fff; color: #111827; }
body.theme-admin-pro .pjob-mat-search,
body.theme-admin-pro .pjob-mat-qty-input {
    background: #fff !important;
    background-color: #fff !important;
    color: #111827 !important;
    border-color: #d1d5db !important;
}
.pjob-mat-results { border: 1px solid #e5e7eb; border-radius: 9px; overflow: hidden; margin-top: 4px; max-height: 200px; overflow-y: auto; display: none; }
.pjob-mat-option { padding: 9px 12px; font-size: 12px; cursor: pointer; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center; }
.pjob-mat-option:last-child { border-bottom: none; }
.pjob-mat-option:hover { background: #f5f3ff; }
.pjob-mat-option .mat-name { font-weight: 700; color: #111827; }
.pjob-mat-option .mat-meta { font-size: 11px; color: #9ca3af; }
.pjob-mat-option .mat-price { font-size: 12px; font-weight: 700; color: #7c5cfc; white-space: nowrap; }
.pjob-mat-row   { display: flex; align-items: center; gap: 8px; padding: 8px 10px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 9px; margin-bottom: 6px; }
.pjob-mat-name  { font-size: 12px; font-weight: 700; color: #111827; flex: 1; }
.pjob-mat-qty   { font-size: 12px; color: #374151; }
.pjob-mat-sub   { font-size: 12px; font-weight: 700; color: #7c5cfc; white-space: nowrap; }
.pjob-mat-del   { background: #fee2e2; color: #ef4444; border: none; border-radius: 6px; padding: 3px 8px; font-size: 11px; cursor: pointer; }
.pjob-mat-total { text-align: right; font-size: 13px; font-weight: 800; color: #111827; padding: 8px 0 0; border-top: 1px dashed #e5e7eb; margin-top: 4px; }
.pjob-mat-add-row { display: flex; gap: 6px; margin-top: 10px; }
.pjob-mat-qty-input:focus { border-color: #7c5cfc; }
.pjob-mat-add-btn { background: #7c5cfc; color: #fff; border: none; border-radius: 8px; padding: 7px 14px; font-size: 12px; font-weight: 700; cursor: pointer; }

/* ── QC Rating stars ─────────────────────────────────────────── */
.qc-stars { display: flex; gap: 3px; }
.qc-star  { font-size: 20px; cursor: pointer; line-height: 1; color: #d1d5db; transition: color .1s; }
.qc-star.filled { color: #f59e0b; }
.qc-rating-display { display: inline-flex; align-items: center; gap: 4px; }
.qc-star-display   { font-size: 16px; }
.qc-star-display.filled { color: #f59e0b; }
.qc-star-display.empty  { color: #d1d5db; }

/* ── Advance modal fields ────────────────────────────────────── */
.adv-field      { margin-bottom: 14px; }
.adv-field label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #374151; margin-bottom: 5px; }
.adv-field input, .adv-field textarea, .adv-field select {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 9px 12px;
    font-size: 13px;
    color: #111827;
    background: #fff;
    box-sizing: border-box;
    outline: none;
}
.adv-field input::placeholder, .adv-field textarea::placeholder { color: #9ca3af; }
.adv-field input:focus, .adv-field textarea:focus {
    border-color: #7c5cfc;
    box-shadow: 0 0 0 3px rgba(124, 92, 252, 0.12);
}
body.theme-admin-pro #advanceModal .adv-field input,
body.theme-admin-pro #advanceModal .adv-field textarea,
body.theme-admin-pro #advanceModal .adv-field select {
    background: #fff !important;
    background-color: #fff !important;
    color: #111827 !important;
    border-color: #d1d5db !important;
}
</style>
@endsection

@section('content')
<div class="pjob-page">

    <a href="{{ $sectionDashboardUrl ?? route('production.index') }}" class="pjob-back">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Back to {{ $stages[$job->current_stage] ?? 'Section' }} Dashboard
    </a>

    {{-- Hero --}}
    <div class="pjob-hero">
        <div class="pjob-hero-top">
            <div>
                <div class="pjob-num">{{ $job->job_number }}</div>
                <div class="pjob-title-h">{{ $job->title }}</div>
                <div class="pjob-cust">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
                    {{ $job->customer_name }}
                    @if($job->customer_phone)
                    &nbsp;·&nbsp;
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.62 3.23 2 2 0 0 1 3.62 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 6 6l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16z"/></svg>
                    {{ $job->customer_phone }}
                    @endif
                </div>
            </div>
            <div class="pjob-actions">
                <div class="pjob-stage-badge">
                    <div class="pjob-stage-dot" style="background:{{ \App\ProductionJob::stageColor($job->current_stage) }}"></div>
                    {{ $stages[$job->current_stage] }}
                </div>
            </div>
        </div>

        {{-- Pipeline progress bar --}}
        <div class="pjob-pipeline" style="margin-top:20px;">
            @php $stageKeys = array_keys($stages); $hitCurrent = false; @endphp
            @foreach($stages as $sk => $sl)
            @php
                if ($sk === $job->current_stage) $hitCurrent = true;
                $isDone   = ! $hitCurrent || $sk === $job->current_stage;
                $isActive = $sk === $job->current_stage;
                $isPast   = ! $isActive && array_search($sk, $stageKeys) < array_search($job->current_stage, $stageKeys);
            @endphp
            <div class="pjob-pip-step">
                <div class="pjob-pip-dot {{ $isPast ? 'done' : ($isActive ? 'active' : '') }}">
                    @if($isPast) ✓ @else {{ array_search($sk, $stageKeys) + 1 }} @endif
                </div>
                <div class="pjob-pip-label {{ $isActive ? 'active' : '' }}">{{ $sl }}</div>
            </div>
            @if(! $loop->last)
            <div class="pjob-pip-line {{ $isPast ? 'done' : '' }}"></div>
            @endif
            @endforeach
        </div>

        {{-- Action buttons --}}
        <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;">
            @if($canAdvance && $nextStage)
            <button class="pjob-btn pjob-btn-adv" onclick="openAdvanceModal()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                Move to {{ $stages[$nextStage] }}
            </button>
            @endif
            {{-- Task start / end (for assigned team member) --}}
            @if($canDoTask && $job->current_stage !== 'completed')
                @if($currentStageRecord && ! $currentStageRecord->task_started_at)
                <button class="pjob-btn task-btn task-btn-start" id="btnStartTask" onclick="startTask()">
                    ▶ Start Task
                </button>
                @elseif($currentStageRecord && $currentStageRecord->task_started_at && ! $currentStageRecord->task_ended_at)
                <button class="pjob-btn task-btn task-btn-end" id="btnEndTask" onclick="endTask()">
                    ⏹ End Task
                </button>
                @elseif($currentStageRecord && $currentStageRecord->task_ended_at)
                <span class="pjob-btn" style="background:rgba(255,255,255,.15);color:#86efac;">✔ Task Completed</span>
                @endif
            @endif
            @if($isAdmin)
            <a href="{{ route('production.edit', $job) }}" class="pjob-btn pjob-btn-edit">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit
            </a>
            <a href="{{ route('production.detail', $job) }}" class="pjob-btn pjob-btn-edit" style="background:rgba(245,158,11,.2);border-color:rgba(245,158,11,.4);">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 17H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l4 4v6"/><path d="M14 21H9M17 17v4M19 19h-4"/></svg>
                Cost Detail
            </a>
            @endif
            @if($job->inquiry && $isAdmin)
            <a href="{{ route('admin.whatsapp.inquiries.show', $job->inquiry_id) }}" class="pjob-btn pjob-btn-inq">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Linked Inquiry
            </a>
            @endif
            @if($isAdmin && $job->current_stage === 'completed')
                @if($job->isConverted() && $job->product_id)
                <a href="{{ route('products.index', ['view_product' => $job->product_id]) }}" class="pjob-btn" style="background:rgba(34,197,94,.25);border-color:rgba(34,197,94,.4);color:#86efac;">
                    ✓ View Product
                </a>
                @else
                <button type="button" class="pjob-btn" style="background:rgba(34,197,94,.3);border-color:rgba(34,197,94,.5);color:#fff;" onclick="openConvertModal({{ $job->id }})">
                    Convert to Product
                </button>
                @endif
            @endif
        </div>
    </div>

    {{-- Body grid --}}
    <div class="pjob-grid">

        {{-- LEFT COLUMN --}}
        <div>

            {{-- Job details card --}}
            <div class="pjob-card">
                <div class="pjob-card-head">
                    <div class="pjob-card-icon" style="background:#ede9fe;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7c5cfc" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6M9 13h4"/></svg>
                    </div>
                    <h3>Job Details</h3>
                </div>
                <div class="pjob-card-body">
                    <div class="pjob-info-row">
                        <span class="pjob-info-label">Priority</span>
                        <span class="pjob-info-val">
                            <span style="background:{{ \App\ProductionJob::priorityColor($job->priority) }}22;color:{{ \App\ProductionJob::priorityColor($job->priority) }};padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700;">{{ ucfirst($job->priority) }}</span>
                        </span>
                    </div>
                    @if($job->due_date)
                    <div class="pjob-info-row">
                        <span class="pjob-info-label">Due Date</span>
                        <span class="pjob-info-val {{ $job->due_date->isPast() && $job->current_stage !== 'completed' ? 'tw-text-red-500 tw-font-bold' : '' }}">{{ $job->due_date->format('d M Y') }}</span>
                    </div>
                    @endif
                    <div class="pjob-info-row">
                        <span class="pjob-info-label">Created By</span>
                        <span class="pjob-info-val">{{ $job->creator->name ?? '—' }}</span>
                    </div>
                    <div class="pjob-info-row">
                        <span class="pjob-info-label">Created</span>
                        <span class="pjob-info-val">{{ $job->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                    @if($job->description)
                    <div class="pjob-info-row">
                        <span class="pjob-info-label">Description</span>
                        <span class="pjob-info-val" style="white-space:pre-wrap;">{{ $job->description }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Files card --}}
            <div class="pjob-card">
                <div class="pjob-card-head">
                    <div class="pjob-card-icon" style="background:#fef3c7;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
                    </div>
                    <h3>Files & Assets</h3>
                </div>
                <div class="pjob-card-body">
                    {{-- Google Drive --}}
                    <div style="margin-bottom:14px;">
                        <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;display:flex;align-items:center;gap:5px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="#34a853"><path d="M6.28 3l5.72 9.9L3 19.5h4.28L12 11l4.72 8.5H21L12 3H6.28z"/></svg>
                            Google Drive Folder
                        </div>
                        <div class="pjob-drive-row">
                            <input type="url" class="pjob-drive-input" id="driveUrl" value="{{ $job->google_drive_url }}" placeholder="Paste Google Drive link…">
                            <button class="pjob-drive-btn" onclick="saveDriveUrl()">Save</button>
                            @if($job->google_drive_url)
                            <a href="{{ $job->google_drive_url }}" target="_blank" style="background:#e0f2fe;color:#0284c7;border:none;border-radius:8px;padding:7px 14px;font-size:12px;font-weight:700;text-decoration:none;">Open ↗</a>
                            @endif
                        </div>
                    </div>

                    <div style="border-top:1px dashed #e5e7eb;padding-top:14px;">
                        <div style="font-size:12px;font-weight:700;color:#374151;margin-bottom:10px;">Uploaded Files ({{ $job->files->count() }})</div>

                        @forelse($job->files as $file)
                        <div class="pjob-file-item" data-file="{{ $file->id }}">
                            <div class="pjob-file-icon">{{ $file->icon }}</div>
                            <div style="flex:1;min-width:0;">
                                <div class="pjob-file-name">{{ $file->original_name }}</div>
                                <div class="pjob-file-meta">
                                    @if($file->label) <strong>{{ $file->label }}</strong> · @endif
                                    {{ $file->uploader->name ?? '—' }} · {{ $file->created_at->format('d M, h:i A') }}
                                </div>
                            </div>
                            <div class="pjob-file-actions">
                                <a href="{{ route('production.file.download', $file) }}" class="pjob-file-btn pjob-file-dl">Download</a>
                                <button class="pjob-file-btn pjob-file-del" onclick="deleteFile({{ $file->id }}, this)">✕</button>
                            </div>
                        </div>
                        @empty
                        <div style="text-align:center;padding:16px;color:#d1d5db;font-size:12px;">No files uploaded yet</div>
                        @endforelse

                        {{-- Upload form --}}
                        <div style="margin-top:12px;">
                            <form id="uploadForm" enctype="multipart/form-data">
                                @csrf
                                <div class="pjob-upload-area" id="dropZone" onclick="document.getElementById('fileInput').click()">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5" style="margin:0 auto;display:block;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
                                    <p>Click or drag files to upload</p>
                                    <p style="font-size:10px;margin-top:3px;">Max 20 MB per file</p>
                                </div>
                                <input type="file" id="fileInput" name="files[]" multiple style="display:none;">
                                <div id="filePreview" style="margin-top:8px;display:none;">
                                    <div id="fileList" style="margin-bottom:8px;font-size:12px;color:#374151;"></div>
                                    <input type="text" id="fileLabel" placeholder="Label (e.g. Logo, Brief)…" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:6px 10px;font-size:12px;margin-bottom:7px;">
                                    <button type="button" onclick="uploadFiles()" style="background:#7c5cfc;color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:12px;font-weight:700;cursor:pointer;width:100%;">Upload Files</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Current section work (team members) --}}
            @php
                $cs = $job->current_stage;
                $myPlan = $job->sectionPlans->firstWhere('stage', $cs);
                $myTasks = $job->tasks->where('stage', $cs);
                $myAssignees = $job->assignments->where('stage', $cs);
                $hasMySectionWork = $cs !== 'completed' && (
                    ($myPlan && ($myPlan->estimated_minutes || $myPlan->notes))
                    || $myTasks->count()
                    || $myAssignees->count()
                );
            @endphp
            @if($canDoTask && $hasMySectionWork)
            <div class="pjob-card">
                <div class="pjob-card-head">
                    <div class="pjob-card-icon" style="background:{{ \App\ProductionJob::stageColor($cs) }}22;">📋</div>
                    <h3>Your Section — {{ $stages[$cs] ?? ucfirst($cs) }}</h3>
                </div>
                <div class="pjob-card-body">
                    @if($myPlan && $myPlan->estimated_minutes)
                    <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:10px;">⏱ Section estimate: {{ $myPlan->formattedEstimate() }}</div>
                    @endif
                    @if($myPlan && $myPlan->notes)
                    <div style="font-size:12px;color:#6b7280;margin-bottom:10px;white-space:pre-wrap;line-height:1.5;">{{ $myPlan->notes }}</div>
                    @endif
                    @if($myAssignees->count())
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px;">
                        @foreach($myAssignees as $a)
                        <span style="font-size:11px;background:#f3f4f6;color:#374151;padding:3px 10px;border-radius:20px;font-weight:600;">
                            {{ \App\ProductionJobAssignment::userDisplayName($a->user) }}
                        </span>
                        @endforeach
                    </div>
                    @endif
                    @if($myTasks->count())
                    <ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:6px;">
                        @foreach($myTasks as $t)
                        <li style="display:flex;justify-content:space-between;gap:10px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:8px 10px;font-size:12px;">
                            <span style="font-weight:600;color:#111827;">{{ $t->title }}</span>
                            @if($t->formattedEstimate())
                            <span style="font-size:11px;font-weight:800;color:{{ \App\ProductionJob::stageColor($cs) }};">{{ $t->formattedEstimate() }}</span>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </div>
            </div>
            @endif
            {{-- Section plans & tasks --}}
            @if($isAdmin && ($job->sectionPlans->count() || $job->tasks->count() || $job->assignments->count()))
            <div class="pjob-card">
                <div class="pjob-card-head">
                    <div class="pjob-card-icon" style="background:#ede9fe;">📋</div>
                    <h3>Job Plan</h3>
                </div>
                <div class="pjob-card-body">
                    @foreach(\App\ProductionStageEmployee::workableStages() as $sk)
                    @php
                        $plan = $job->sectionPlans->firstWhere('stage', $sk);
                        $stageTasks = $job->tasks->where('stage', $sk);
                        $stageAssignees = $job->assignments->where('stage', $sk);
                    @endphp
                    @if($plan || $stageTasks->count() || $stageAssignees->count())
                    <div style="margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid #f3f4f6;">
                        <div style="font-size:12px;font-weight:800;color:{{ \App\ProductionJob::stageColor($sk) }};margin-bottom:6px;">
                            {{ $stages[$sk] ?? ucfirst($sk) }}
                            @if($plan && $plan->estimated_minutes)
                            <span style="font-weight:600;color:#6b7280;"> · Est. {{ $plan->formattedEstimate() }}</span>
                            @endif
                        </div>
                        @if($stageAssignees->count())
                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
                            @foreach($stageAssignees as $a)
                            <span style="font-size:11px;background:#f3f4f6;color:#374151;padding:3px 10px;border-radius:20px;font-weight:600;">
                                {{ \App\ProductionJobAssignment::userDisplayName($a->user) }}
                            </span>
                            @endforeach
                        </div>
                        @endif
                        @if($stageTasks->count())
                        <ul style="margin:0;padding-left:18px;font-size:12px;color:#374151;">
                            @foreach($stageTasks as $t)
                            <li style="margin-bottom:4px;">
                                {{ $t->title }}
                                @if($t->estimated_minutes)<span style="color:#9ca3af;"> ({{ $t->formattedEstimate() }})</span>@endif
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
            @endif
            {{-- Materials card (production stage) --}}
            @if(in_array($job->current_stage, ['production']) || $job->materials->count() > 0)
            <div class="pjob-card" id="materialsCard">
                <div class="pjob-card-head">
                    <div class="pjob-card-icon" style="background:#fef3c7;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5"><path d="M20 7H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    </div>
                    <h3>Raw Materials Issued</h3>
                    <span style="margin-left:auto;font-size:12px;font-weight:700;color:#f59e0b;" id="matTotal">
                        Rs {{ number_format($job->materials->sum(fn($m) => $m->quantity * $m->unit_price), 2) }}
                    </span>
                </div>
                <div class="pjob-card-body">

                    {{-- Existing materials list --}}
                    <div id="matList">
                        @forelse($job->materials as $mat)
                        <div class="pjob-mat-row" data-usage="{{ $mat->id }}" data-sub="{{ $mat->subtotal }}">
                            <div style="flex:1;min-width:0;">
                                <div class="pjob-mat-name">{{ $mat->material->name ?? '—' }}</div>
                                <div class="pjob-mat-qty">{{ $mat->quantity }} {{ $mat->material->unit?->abbreviation }} × Rs {{ number_format($mat->unit_price, 2) }}</div>
                            </div>
                            <span class="pjob-mat-sub">Rs {{ number_format($mat->subtotal, 2) }}</span>
                            @if($canIssueMaterials)
                            <button class="pjob-mat-del" onclick="removeMaterial({{ $mat->id }}, this)">✕</button>
                            @endif
                        </div>
                        @empty
                        <div id="matEmpty" style="text-align:center;padding:12px;color:#d1d5db;font-size:12px;">No materials added yet</div>
                        @endforelse
                    </div>

                    {{-- Issue raw materials (production stage — supervisor or assigned team) --}}
                    @if($canIssueMaterials)
                    <div style="margin-top:12px;border-top:1px dashed #e5e7eb;padding-top:12px;">
                        <div style="font-size:11px;font-weight:700;color:#374151;margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em;">Issue Raw Material</div>
                        <p style="font-size:11px;color:#9ca3af;margin:0 0 8px;">Search inventory and issue materials to this job. Stock will be deducted.</p>
                        <div style="position:relative;">
                            <input type="text" class="pjob-mat-search" id="matSearch" placeholder="Search material name…" autocomplete="off">
                            <div class="pjob-mat-results" id="matResults"></div>
                        </div>
                        <div id="selectedMat" style="display:none;background:#f5f3ff;border:1px solid #ede9fe;border-radius:9px;padding:9px 12px;margin-top:6px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <div>
                                    <div id="selMatName" style="font-size:13px;font-weight:700;color:#111827;"></div>
                                    <div id="selMatMeta" style="font-size:11px;color:#9ca3af;"></div>
                                </div>
                                <button onclick="clearSelectedMat()" style="background:none;border:none;color:#9ca3af;cursor:pointer;font-size:16px;">×</button>
                            </div>
                            <div class="pjob-mat-add-row">
                                <input type="number" id="matQty" class="pjob-mat-qty-input" placeholder="Qty" min="0.001" step="0.001">
                                <button class="pjob-mat-add-btn" onclick="addMaterial()">Issue</button>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- RIGHT COLUMN --}}
        <div>
            @if($isAdmin)
            <div class="pjob-card">
                <div class="pjob-card-head">
                    <div class="pjob-card-icon" style="background:#d1fae5;">💰</div>
                    <h3>Production Cost</h3>
                    <a href="{{ route('production.detail', $job) }}" style="margin-left:auto;font-size:11px;font-weight:700;color:#7c5cfc;text-decoration:none;">Full Detail →</a>
                </div>
                <div class="pjob-card-body">
                    <div class="pjob-info-row">
                        <span class="pjob-info-label">Materials</span>
                        <span class="pjob-info-val">Rs {{ number_format($materialCost, 2) }}</span>
                    </div>
                    <div class="pjob-info-row">
                        <span class="pjob-info-label">Section Labor</span>
                        <span class="pjob-info-val">Rs {{ number_format($stageCost, 2) }}</span>
                    </div>
                    @foreach($stageCosts as $sk => $sc)
                    <div class="pjob-info-row" style="padding-left:12px;">
                        <span class="pjob-info-label" style="min-width:90px;font-size:12px;color:{{ \App\ProductionJob::stageColor($sk) }};">{{ $sc['label'] }}</span>
                        <span class="pjob-info-val" style="font-size:12px;">Rs {{ number_format($sc['amount'], 2) }}</span>
                    </div>
                    @endforeach
                    <div class="pjob-info-row" style="background:#f5f3ff;margin:8px -18px -16px;padding:12px 18px;border-radius:0 0 14px 14px;">
                        <span class="pjob-info-label" style="color:#5b21b6;font-weight:800;">Total Cost</span>
                        <span class="pjob-info-val" style="font-weight:900;color:#5b21b6;font-size:16px;">Rs {{ number_format($totalCost, 2) }}</span>
                    </div>
                    @if($jobRevenue > 0)
                    <div class="pjob-info-row" style="margin-top:10px;">
                        <span class="pjob-info-label">Revenue</span>
                        <span class="pjob-info-val" style="color:#2563eb;font-weight:700;">Rs {{ number_format($jobRevenue, 2) }}</span>
                    </div>
                    <div class="pjob-info-row">
                        <span class="pjob-info-label">Profit / Loss</span>
                        <span class="pjob-info-val" style="font-weight:800;color:{{ $jobProfit >= 0 ? '#15803d' : '#dc2626' }};">Rs {{ number_format($jobProfit, 2) }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Task Timing card --}}
            @if($currentStageRecord && $job->current_stage !== 'completed')
            <div class="pjob-card">
                <div class="pjob-card-head">
                    <div class="pjob-card-icon" style="background:#ede9fe;">⏱</div>
                    <h3 id="taskCardTitle">Task Status — {{ $stages[$job->current_stage] }}</h3>
                </div>
                <div class="pjob-card-body">
                    <div class="pjob-task-card {{ $currentStageRecord->task_ended_at ? 'pjob-task-done' : ($currentStageRecord->task_started_at ? 'pjob-task-active' : 'pjob-task-idle') }}" id="taskCard">
                        <div class="pjob-task-label">
                            @if($currentStageRecord->task_ended_at) ✅ Task Completed
                            @elseif($currentStageRecord->task_started_at) 🟡 In Progress
                            @else ⏳ Not Started
                            @endif
                        </div>
                        @if($currentStageRecord->task_started_at)
                        <div class="pjob-task-row">
                            <div class="pjob-task-time">
                                <div>Started: <strong>{{ $currentStageRecord->task_started_at->format('d M Y, h:i A') }}</strong></div>
                                @if($currentStageRecord->task_ended_at)
                                <div>Ended: <strong>{{ $currentStageRecord->task_ended_at->format('d M Y, h:i A') }}</strong></div>
                                @php
                                    $durMin = $currentStageRecord->task_started_at->diffInMinutes($currentStageRecord->task_ended_at);
                                    $durStr = $durMin >= 60 ? floor($durMin/60).'h '.($durMin%60).'m' : $durMin.'m';
                                @endphp
                                <div style="margin-top:4px;font-weight:700;color:#10b981;">Duration: {{ $durStr }}</div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Stage history card --}}
            <div class="pjob-card">
                <div class="pjob-card-head">
                    <div class="pjob-card-icon" style="background:#d1fae5;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    </div>
                    <h3>Stage History</h3>
                </div>
                <div class="pjob-card-body">
                    <div class="pjob-timeline">
                        @forelse($job->stageHistory as $stage)
                        <div class="pjob-tl-item">
                            <div class="pjob-tl-dot" style="background:{{ \App\ProductionJob::stageColor($stage->stage) }}"></div>
                            <div class="pjob-tl-meta">
                                {{ $stage->started_at->format('d M Y, h:i A') }}
                                · {{ $stage->movedBy->name ?? '—' }}
                            </div>
                            <div class="pjob-tl-body">
                                <div class="pjob-tl-stage" style="color:{{ \App\ProductionJob::stageColor($stage->stage) }}">
                                    → {{ \App\ProductionJob::allStages()[$stage->stage] ?? $stage->stage }}
                                </div>
                                @if($stage->notes)
                                <div class="pjob-tl-notes">{{ $stage->notes }}</div>
                                @endif
                                @php $stageRatings = ($employeeRatings[$stage->stage] ?? collect()); @endphp
                                @if($stageRatings->count())
                                @php $raterLabel = \App\ProductionJob::allStages()[$stageRatings->first()->rater_stage] ?? 'next section'; @endphp
                                <div style="margin-top:6px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:8px 10px;">
                                    <div style="font-size:10px;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Team ratings by {{ $raterLabel }}</div>
                                    @foreach($stageRatings as $r)
                                    @php
                                        $ru = $r->ratedUser;
                                        $rname = $ru ? trim(($ru->surname ?? '') . ' ' . ($ru->first_name ?? '') . ' ' . ($ru->last_name ?? '')) : '';
                                        $rname = $rname ?: ($ru->username ?? 'Employee');
                                    @endphp
                                    <div style="margin-bottom:{{ $loop->last ? '0' : '6px' }};">
                                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                            <span style="font-size:12px;font-weight:700;color:#111827;">{{ $rname }}</span>
                                            <span>@for($i = 1; $i <= 5; $i++)<span style="font-size:13px;color:{{ $i <= $r->rating ? '#f59e0b' : '#e5e7eb' }};">★</span>@endfor</span>
                                            <span style="font-size:11px;font-weight:700;color:#92400e;">{{ $r->rating }}/5</span>
                                        </div>
                                        @if($r->comment)
                                        <div style="font-size:11px;color:#374151;font-style:italic;margin-top:2px;">"{{ $r->comment }}"</div>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                                @if($stage->completed_at)
                                <div style="font-size:10px;color:#9ca3af;margin-top:3px;">Completed: {{ $stage->completed_at->format('d M Y, h:i A') }}</div>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div style="text-align:center;color:#d1d5db;font-size:12px;padding:16px;">No history yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- QC Quality Rating card (visible when production stage has been rated) --}}
            @if($productionStageRecord && $productionStageRecord->quality_rating)
            <div class="pjob-card">
                <div class="pjob-card-head">
                    <div class="pjob-card-icon" style="background:#fef9c3;">⭐</div>
                    <h3>QC Rating for Production</h3>
                </div>
                <div class="pjob-card-body">
                    <div class="qc-rating-display" style="margin-bottom:8px;">
                        @for($i = 1; $i <= 5; $i++)
                        <span class="qc-star-display {{ $i <= $productionStageRecord->quality_rating ? 'filled' : 'empty' }}">★</span>
                        @endfor
                        <span style="font-size:14px;font-weight:800;color:#111827;margin-left:6px;">{{ $productionStageRecord->quality_rating }}/5</span>
                    </div>
                    @if($productionStageRecord->quality_comment)
                    <div style="background:#fafafa;border:1px solid #e5e7eb;border-radius:8px;padding:10px 12px;font-size:13px;color:#374151;font-style:italic;">
                        "{{ $productionStageRecord->quality_comment }}"
                    </div>
                    @endif
                    @if($productionStageRecord->movedBy)
                    <div style="font-size:11px;color:#9ca3af;margin-top:6px;">Rated by {{ $productionStageRecord->movedBy->name ?? '—' }}</div>
                    @endif
                </div>
            </div>
            @endif

            @if($job->inquiry && $isAdmin)
            {{-- Linked inquiry card (admin only) --}}
            <div class="pjob-card">
                <div class="pjob-card-head">
                    <div class="pjob-card-icon" style="background:#dcfce7;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <h3>Linked WhatsApp Inquiry</h3>
                </div>
                <div class="pjob-card-body">
                    <div class="pjob-info-row">
                        <span class="pjob-info-label">Customer</span>
                        <span class="pjob-info-val">{{ $job->inquiry->customer_name ?? '—' }}</span>
                    </div>
                    <div class="pjob-info-row">
                        <span class="pjob-info-label">Phone</span>
                        <span class="pjob-info-val">{{ $job->inquiry->phone_number ?? '—' }}</span>
                    </div>
                    <div class="pjob-info-row">
                        <span class="pjob-info-label">Category</span>
                        <span class="pjob-info-val">{{ $job->inquiry->inquiry_category ?? '—' }}</span>
                    </div>
                    <div class="pjob-info-row">
                        <span class="pjob-info-label">Agent</span>
                        <span class="pjob-info-val">{{ $job->inquiry->agent->name ?? '—' }}</span>
                    </div>
                    <div style="margin-top:10px;display:flex;gap:7px;flex-wrap:wrap;">
                        <a href="{{ route('admin.whatsapp.inquiries.show', $job->inquiry_id) }}" style="background:#ede9fe;color:#7c5cfc;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;">View Inquiry</a>
                        <a href="{{ route('whatsapp.inbox') }}?phone={{ $job->inquiry->phone_number }}" style="background:#dcfce7;color:#16a34a;padding:6px 14px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;">Open in Inbox</a>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

</div>

{{-- Advance stage modal --}}
<div id="advanceModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:18px;padding:28px;width:480px;max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.2);max-height:90vh;overflow-y:auto;">
        <h3 style="font-size:17px;font-weight:800;color:#111827;margin:0 0 4px;">Move to {{ $nextStage ? $stages[$nextStage] : '' }}</h3>
        <p style="font-size:13px;color:#6b7280;margin:0 0 18px;">Add handover details before advancing to the next stage.</p>

        <div class="adv-field">
            <label>Handover Notes</label>
            <textarea id="advNotes" rows="3" placeholder="Notes for the next team (optional)…"></textarea>
        </div>

        <div class="adv-field">
            <label>{{ $stages[$job->current_stage] }} Stage Rate (Rs)</label>
            <input type="number" id="advRate" step="0.01" min="0" placeholder="0.00">
            <div style="font-size:11px;color:#9ca3af;margin-top:3px;">Amount charged for work done in {{ $stages[$job->current_stage] }} stage</div>
        </div>
        <div class="adv-field">
            <label>Rate Notes</label>
            <input type="text" id="advRateNotes" placeholder="e.g. 3 hours @ Rs 2000/hr (optional)">
        </div>

        {{-- QC stage: also rate the production work --}}
        @if($job->current_stage === 'quality')
        <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:10px;padding:14px;margin-bottom:14px;">
            <div style="font-size:13px;font-weight:800;color:#92400e;margin-bottom:10px;">⭐ Rate Production Team's Work</div>
            <div class="adv-field" style="margin-bottom:10px;">
                <label>Quality Rating (1–5 stars)</label>
                <div class="qc-stars" id="advStars">
                    <span class="qc-star" data-val="1" onclick="setRating(1)">★</span>
                    <span class="qc-star" data-val="2" onclick="setRating(2)">★</span>
                    <span class="qc-star" data-val="3" onclick="setRating(3)">★</span>
                    <span class="qc-star" data-val="4" onclick="setRating(4)">★</span>
                    <span class="qc-star" data-val="5" onclick="setRating(5)">★</span>
                </div>
                <input type="hidden" id="advRating" value="">
            </div>
            <div class="adv-field" style="margin-bottom:0;">
                <label>Quality Comment</label>
                <textarea id="advQualityComment" rows="2" placeholder="Feedback for the production team…"></textarea>
            </div>
        </div>
        @endif

        <div style="display:flex;gap:8px;margin-top:4px;">
            <button onclick="doAdvance()" style="flex:1;background:#10b981;color:#fff;border:none;border-radius:10px;padding:11px;font-size:14px;font-weight:700;cursor:pointer;">Confirm Move</button>
            <button onclick="document.getElementById('advanceModal').style.display='none'" style="background:#f3f4f6;color:#374151;border:none;border-radius:10px;padding:11px 18px;font-size:13px;font-weight:600;cursor:pointer;">Cancel</button>
        </div>
    </div>
</div>

<div id="prodToast" class="prod-toast"></div>

@if($isAdmin && $job->current_stage === 'completed')
@include('production.partials.convert-product-modal')
@endif

@endsection

@section('javascript')
@if($isAdmin && $job->current_stage === 'completed')
@include('production.partials.convert-product-script')
@endif
<script>
const ADVANCE_URL   = '{{ route('production.advance', $job) }}';
const UPLOAD_URL    = '{{ route('production.files.upload', $job) }}';
const DRIVE_URL     = '{{ route('production.drive.update', $job) }}';
const TASK_START_URL= '{{ route('production.task.start', $job) }}';
const TASK_END_URL  = '{{ route('production.task.end', $job) }}';
const MAT_SEARCH_URL= '{{ route('production.materials.search', $job) }}';
const MAT_ADD_URL   = '{{ route('production.materials.add', $job) }}';
const MAT_REMOVE_BASE = '{{ url('production/' . $job->id . '/materials') }}';
const CSRF          = '{{ csrf_token() }}';

function showToast(msg, isError = false) {
    const t = document.getElementById('prodToast');
    t.textContent = msg;
    t.style.background = isError ? '#ef4444' : '#111827';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

// ── Task start / end ────────────────────────────────────────
function startTask() {
    fetch(TASK_START_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
    .then(r => r.json())
    .then(d => {
        if (d.success) { showToast('Task started!'); setTimeout(() => location.reload(), 700); }
        else showToast(d.message || 'Error', true);
    });
}
function endTask() {
    if (!confirm('Mark task as ended?')) return;
    fetch(TASK_END_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
    .then(r => r.json())
    .then(d => {
        if (d.success) { showToast('Task ended! Duration: ' + d.duration); setTimeout(() => location.reload(), 900); }
        else showToast(d.message || 'Error', true);
    });
}

// ── Advance modal ────────────────────────────────────────────
let advRatingVal = 0;
function openAdvanceModal() {
    document.getElementById('advanceModal').style.display = 'flex';
}
function setRating(val) {
    advRatingVal = val;
    document.getElementById('advRating').value = val;
    document.querySelectorAll('#advStars .qc-star').forEach((s, i) => {
        s.classList.toggle('filled', i < val);
    });
}
function doAdvance() {
    const body = {
        notes:            document.getElementById('advNotes')?.value ?? '',
        stage_rate:       document.getElementById('advRate')?.value || null,
        stage_rate_notes: document.getElementById('advRateNotes')?.value || null,
    };
    const qualEl  = document.getElementById('advRating');
    const commEl  = document.getElementById('advQualityComment');
    if (qualEl && qualEl.value) {
        body.quality_rating  = parseInt(qualEl.value);
        body.quality_comment = commEl?.value || null;
    }
    fetch(ADVANCE_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(body)
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) { showToast('Moved to ' + d.stage_label + '!'); setTimeout(() => location.reload(), 900); }
        else showToast(d.message || 'Error', true);
    })
    .catch(() => showToast('Request failed.', true));
    document.getElementById('advanceModal').style.display = 'none';
}

// ── Google Drive ─────────────────────────────────────────────
function saveDriveUrl() {
    const url = document.getElementById('driveUrl').value;
    fetch(DRIVE_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ google_drive_url: url })
    })
    .then(r => r.json())
    .then(d => { if (d.success) showToast('Drive link saved!'); else showToast('Failed.', true); })
    .catch(() => showToast('Request failed.', true));
}

// ── File upload ──────────────────────────────────────────────
const fileInput = document.getElementById('fileInput');
fileInput.addEventListener('change', () => {
    const preview = document.getElementById('filePreview');
    const list    = document.getElementById('fileList');
    if (fileInput.files.length) {
        list.innerHTML = Array.from(fileInput.files).map(f => `<div>📎 ${f.name}</div>`).join('');
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
});

const dropZone = document.getElementById('dropZone');
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    fileInput.files = e.dataTransfer.files;
    fileInput.dispatchEvent(new Event('change'));
});

function uploadFiles() {
    const fd = new FormData();
    fd.append('_token', CSRF);
    Array.from(fileInput.files).forEach((f, i) => { fd.append(`files[${i}]`, f); });
    const lbl = document.getElementById('fileLabel').value;
    if (lbl) fd.append('file_labels[0]', lbl);

    fetch(UPLOAD_URL, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => { if (d.success) { showToast('Files uploaded!'); setTimeout(() => location.reload(), 800); } else showToast('Upload failed.', true); })
    .catch(() => showToast('Upload failed.', true));
}

function deleteFile(id, btn) {
    if (! confirm('Delete this file?')) return;
    const url = '{{ url('production/files') }}/' + id;
    fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } })
    .then(r => r.json())
    .then(d => {
        if (d.success) { document.querySelector(`[data-file="${id}"]`).remove(); showToast('Deleted.'); }
        else showToast('Failed.', true);
    });
}

// ── Materials ────────────────────────────────────────────────
let selectedMaterial = null;
let matSearchTimeout = null;
const matSearch  = document.getElementById('matSearch');
const matResults = document.getElementById('matResults');

if (matSearch) {
    matSearch.addEventListener('input', () => {
        clearTimeout(matSearchTimeout);
        const q = matSearch.value.trim();
        if (!q) { matResults.style.display = 'none'; return; }
        matSearchTimeout = setTimeout(async () => {
            const r = await fetch(`${MAT_SEARCH_URL}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } });
            const items = await r.json();
            if (!items.length) { matResults.style.display = 'none'; return; }
            matResults.innerHTML = items.map(m => `
                <div class="pjob-mat-option" onclick='selectMat(${JSON.stringify(m)})'>
                    <div><div class="mat-name">${m.name}</div><div class="mat-meta">${m.category||''} · Stock: ${m.stock} ${m.unit}</div></div>
                    <div class="mat-price">Rs ${parseFloat(m.price).toFixed(2)} / ${m.unit}</div>
                </div>
            `).join('');
            matResults.style.display = 'block';
        }, 280);
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#matSearch') && !e.target.closest('#matResults')) {
            matResults.style.display = 'none';
        }
    });
}

function selectMat(mat) {
    selectedMaterial = mat;
    matResults.style.display = 'none';
    matSearch.value = '';
    document.getElementById('selMatName').textContent = mat.name;
    document.getElementById('selMatMeta').textContent = `${mat.category||''} · Rs ${parseFloat(mat.price).toFixed(2)} / ${mat.unit||'unit'} · Stock: ${mat.stock} ${mat.unit}`;
    document.getElementById('selectedMat').style.display = 'block';
    document.getElementById('matQty').value = '';
    document.getElementById('matQty').focus();
}

function clearSelectedMat() {
    selectedMaterial = null;
    document.getElementById('selectedMat').style.display = 'none';
}

async function addMaterial() {
    if (!selectedMaterial) return;
    const qty = parseFloat(document.getElementById('matQty').value);
    if (!qty || qty <= 0) { showToast('Enter a valid quantity.', true); return; }

    const r = await fetch(MAT_ADD_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ material_id: selectedMaterial.id, quantity: qty }),
    });
    const d = await r.json();
    if (d.success) {
        const u = d.usage;
        const emptyEl = document.getElementById('matEmpty');
        if (emptyEl) emptyEl.remove();
        const row = document.createElement('div');
        row.className = 'pjob-mat-row';
        row.dataset.usage = u.id;
        row.dataset.sub   = u.subtotal;
        row.innerHTML = `
            <div style="flex:1;min-width:0;">
                <div class="pjob-mat-name">${u.name}</div>
                <div class="pjob-mat-qty">${u.quantity} ${u.unit} × Rs ${parseFloat(u.unit_price).toFixed(2)}</div>
            </div>
            <span class="pjob-mat-sub">Rs ${parseFloat(u.subtotal).toFixed(2)}</span>
            <button class="pjob-mat-del" onclick="removeMaterial(${u.id}, this)">✕</button>
        `;
        document.getElementById('matList').appendChild(row);
        recalcMatTotal();
        clearSelectedMat();
        showToast('Material issued!');
    } else {
        showToast(d.message || 'Failed.', true);
    }
}

async function removeMaterial(id, btn) {
    if (!confirm('Remove this material?')) return;
    const r = await fetch(`${MAT_REMOVE_BASE}/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    });
    const d = await r.json();
    if (d.success) {
        const row = document.querySelector(`[data-usage="${id}"]`);
        if (row) row.remove();
        recalcMatTotal();
        showToast('Removed.');
    } else showToast(d.message || 'Failed.', true);
}

function recalcMatTotal() {
    let total = 0;
    document.querySelectorAll('.pjob-mat-row').forEach(r => { total += parseFloat(r.dataset.sub || 0); });
    const el = document.getElementById('matTotal');
    if (el) el.textContent = 'Rs ' + total.toFixed(2);
}
</script>
@endsection
