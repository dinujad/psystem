@extends('layouts.app')
@section('title', $stageLabel . ' Dashboard')

@section('css')
<style>
.sec-page    { padding: 0 20px 60px; max-width: 1100px; margin: 0 auto; }
.sec-back    { display: inline-flex; align-items: center; gap: 6px; color: #7c5cfc; font-size: 13px; font-weight: 600; text-decoration: none; margin: 18px 0 14px; }
.sec-back:hover { color: #5b3fd9; text-decoration: none; }

.sec-hero    {
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 18px;
    color: #fff !important;
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 55%, {{ $stageColor }} 100%);
    box-shadow: inset 0 -3px 0 {{ $stageColor }}, 0 4px 20px rgba(15,23,42,.25);
    position: relative;
    overflow: hidden;
}
.sec-hero::before {
    content: '';
    position: absolute;
    right: -40px;
    top: -40px;
    width: 160px;
    height: 160px;
    border-radius: 50%;
    background: {{ $stageColor }}33;
    pointer-events: none;
}
.sec-hero h1 {
    font-size: 22px;
    font-weight: 800;
    margin: 0 0 6px;
    color: #fff !important;
    position: relative;
    z-index: 1;
}
.sec-hero p  {
    margin: 0;
    font-size: 13px;
    color: #fff !important;
    opacity: .9;
    position: relative;
    z-index: 1;
}
.sec-head-info {
    margin-top: 10px;
    font-size: 12px;
    color: #fff !important;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.2);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 20px;
    position: relative;
    z-index: 1;
}
.sec-head-info strong { color: #fff !important; }

/* Beat theme text overrides inside scrollable container */
body.theme-admin-pro #scrollable-container .sec-hero,
body.theme-admin-pro #scrollable-container .sec-hero h1,
body.theme-admin-pro #scrollable-container .sec-hero p,
body.theme-admin-pro #scrollable-container .sec-hero strong,
body.theme-admin-pro #scrollable-container .sec-head-info,
body.theme-admin-pro #scrollable-container .sec-head-info * {
    color: #fff !important;
}

.sec-tabs    { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.sec-tab     { padding: 8px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; text-decoration: none; background: #fff; border: 1px solid #e5e7eb; color: #374151; }
.sec-tab:hover { text-decoration: none; border-color: {{ $stageColor }}; color: {{ $stageColor }}; }
.sec-tab.active { background: {{ $stageColor }}; border-color: {{ $stageColor }}; color: #fff; }
.sec-tab .cnt { opacity: .85; margin-left: 4px; }

.sec-nav     { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.sec-nav a   { font-size: 11px; font-weight: 700; padding: 5px 12px; border-radius: 20px; text-decoration: none; background: #f3f4f6; color: #6b7280; }
.sec-nav a:hover { background: #ede9fe; color: #5b3fd9; text-decoration: none; }
.sec-nav a.active { background: #ede9fe; color: #5b3fd9; }

.sec-bar     { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px 16px; display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; }
.sec-bar input { flex: 1; min-width: 180px; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 13px; }
.sec-btn     { background: {{ $stageColor }}; color: #fff; border: none; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
.sec-btn:hover { opacity: .9; color: #fff; text-decoration: none; }
.sec-btn.outline { background: #fff; color: {{ $stageColor }}; border: 1.5px solid {{ $stageColor }}; }

.sec-list    { display: flex; flex-direction: column; gap: 12px; }
.sec-job     { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
.sec-job-head { padding: 14px 18px; display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-start; justify-content: space-between; border-left: 4px solid {{ $stageColor }}; }
.sec-job-num  { font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; }
.sec-job-title { font-size: 16px; font-weight: 800; color: #111827; margin: 2px 0 6px; }
.sec-job-meta  { font-size: 12px; color: #6b7280; display: flex; flex-wrap: wrap; gap: 12px; }
.sec-status    { font-size: 11px; font-weight: 800; padding: 4px 12px; border-radius: 20px; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
.sec-status.new      { background: #f3f4f6; color: #6b7280; }
.sec-status.progress { background: #fef3c7; color: #b45309; }
.sec-status.ready    { background: #dcfce7; color: #15803d; }

.sec-job-body { padding: 0 18px 14px; font-size: 13px; color: #374151; }
.sec-job-plan {
    background: linear-gradient(180deg, {{ $stageColor }}08 0%, #f9fafb 100%);
    border: 1px solid {{ $stageColor }}33;
    border-radius: 10px;
    padding: 12px 14px;
    margin: 0 18px 14px;
}
.sec-job-plan-title { font-size: 11px; font-weight: 800; color: {{ $stageColor }}; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; }
.sec-plan-est { font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 8px; }
.sec-plan-notes { font-size: 12px; color: #6b7280; margin-bottom: 8px; white-space: pre-wrap; line-height: 1.45; }
.sec-plan-team { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
.sec-plan-team span { font-size: 11px; background: #fff; border: 1px solid #e5e7eb; color: #374151; padding: 3px 10px; border-radius: 20px; font-weight: 600; }
.sec-task-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
.sec-task-list li {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
    background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px 10px; font-size: 12px;
}
.sec-task-list li span:first-child { flex: 1; min-width: 0; color: #111827; font-weight: 600; line-height: 1.4; }
.sec-task-time { font-size: 11px; font-weight: 800; color: {{ $stageColor }}; white-space: nowrap; background: {{ $stageColor }}14; padding: 2px 8px; border-radius: 12px; }
.sec-job-desc { background: #f9fafb; border-radius: 8px; padding: 10px 12px; margin-bottom: 10px; white-space: pre-wrap; line-height: 1.5; }
.sec-tags     { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
.sec-tag      { font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 20px; }

.sec-job-foot { padding: 12px 18px; border-top: 1px solid #f3f4f6; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.sec-action   { border: none; border-radius: 9px; padding: 8px 14px; font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
.sec-action-view  { background: #ede9fe; color: #7c5cfc; }
.sec-action-view:hover { background: #ddd6fe; color: #5b3fd9; text-decoration: none; }
.sec-action-start { background: {{ $stageColor }}; color: #fff; }
.sec-action-end   { background: #10b981; color: #fff; }
.sec-action-move  { background: #059669; color: #fff; }
.sec-action:disabled { opacity: .5; cursor: not-allowed; }

.sec-empty   { text-align: center; padding: 48px 20px; background: #fff; border: 1px dashed #e5e7eb; border-radius: 14px; color: #9ca3af; }
.sec-toast   { position: fixed; bottom: 28px; right: 24px; background: #111827; color: #fff; border-radius: 12px; padding: 12px 20px; font-size: 13px; font-weight: 600; z-index: 9999; display: none; }
.sec-toast.show { display: block; }

/* Incoming-work rating modal */
.sec-modal-ov { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(17,24,39,.55); z-index: 100000; display: none; align-items: center; justify-content: center; padding: 16px; box-sizing: border-box; overflow-y: auto; }
.sec-modal-ov.show { display: flex; }
.sec-modal    { background: #fff; border-radius: 16px; width: 100%; max-width: 460px; max-height: calc(100vh - 32px); display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,.3); margin: auto; }
.sec-modal-head {
    padding: 18px 22px;
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 55%, {{ $stageColor }} 100%);
    color: #fff !important;
    flex-shrink: 0;
    box-shadow: inset 0 -3px 0 {{ $stageColor }};
}
.sec-modal-head h3 { margin: 0; font-size: 16px; font-weight: 800; color: #fff !important; }
.sec-modal-head p  { margin: 4px 0 0; font-size: 12px; color: #fff !important; opacity: .9; }
.sec-modal-head strong { color: #fff !important; }
.sec-modal-body { padding: 16px 20px; overflow-y: auto; flex: 1; }
.sec-modal-intro { font-size: 12px; color: #6b7280; margin-bottom: 14px; }

.sec-rate-emp { border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px 14px; margin-bottom: 12px; }
.sec-rate-emp-top { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
.sec-rate-av  { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; color: #fff; flex-shrink: 0; background: {{ $stageColor }}; }
.sec-rate-name { font-size: 13px; font-weight: 700; color: #111827; flex: 1; min-width: 0; }
.sec-rate-head-tag { font-size: 9px; font-weight: 800; color: #92400e; background: #fef3c7; padding: 2px 7px; border-radius: 20px; }
.sec-stars    { display: flex; gap: 4px; margin-bottom: 8px; }
.sec-star     { font-size: 30px; line-height: 1; cursor: pointer; color: #d1d5db; transition: color .12s, transform .1s; user-select: none; -webkit-tap-highlight-color: transparent; }
.sec-star:active { transform: scale(1.15); }
.sec-star.on  { color: #f59e0b; }
.sec-rate-emp textarea { width: 100%; border: 1px solid #d1d5db; border-radius: 9px; padding: 8px 10px; font-size: 12px; color: #374151; resize: vertical; min-height: 40px; box-sizing: border-box; font-family: inherit; }

.sec-rate-empty { text-align: center; color: #9ca3af; font-size: 12px; padding: 16px; }
.sec-modal-foot { padding: 14px 20px; display: flex; gap: 10px; justify-content: flex-end; flex-shrink: 0; border-top: 1px solid #f3f4f6; }
.sec-modal-btn  { border: none; border-radius: 9px; padding: 10px 18px; font-size: 13px; font-weight: 700; cursor: pointer; }
.sec-modal-btn.cancel  { background: #f3f4f6; color: #6b7280; }
.sec-modal-btn.confirm { background: {{ $stageColor }}; color: #fff; }
.sec-modal-btn:disabled { opacity: .6; cursor: not-allowed; }

@media (max-width: 520px) {
    .sec-modal-ov { padding: 0; align-items: stretch; }
    .sec-modal { max-width: none; max-height: 100vh; height: 100vh; border-radius: 0; margin: 0; }
    .sec-modal-foot { flex-direction: column-reverse; }
    .sec-modal-btn { width: 100%; padding: 12px; }
    .sec-star { font-size: 34px; gap: 8px; }
}
</style>
@endsection

@section('content')
<div class="sec-page">

    @if($isAdmin)
    <a href="{{ route('production.index') }}" class="sec-back">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Full Production Board
    </a>
    @endif

    <div class="sec-hero">
        <h1>{{ $stageLabel }} Dashboard</h1>
        <p>Jobs in your section — start tasks, update work, and move completed jobs forward.</p>
        @if($sectionHead && $sectionHead->user)
        @php
            $hu = $sectionHead->user;
            $hname = trim(($hu->surname ?? '') . ' ' . ($hu->first_name ?? '') . ' ' . ($hu->last_name ?? ''));
        @endphp
        <div class="sec-head-info">
            👑 Section Head: <strong>{{ $hname ?: $hu->username }}</strong>
            @if($sectionHead->whatsapp_number) · 📱 {{ $sectionHead->whatsapp_number }} @endif
        </div>
        @endif
    </div>

    @if(count($myStages) > 1)
    <div class="sec-nav">
        @foreach($myStages as $sk)
        <a href="{{ route('production.section', $sk) }}" class="{{ $sk === $stage ? 'active' : '' }}">{{ $stages[$sk] ?? ucfirst($sk) }}</a>
        @endforeach
    </div>
    @endif

    <div class="sec-tabs">
        @foreach(['all' => 'All', 'new' => 'New', 'progress' => 'In Progress', 'ready' => 'Ready to Move'] as $key => $label)
        <a href="{{ route('production.section', ['stage' => $stage, 'filter' => $key, 'q' => $search]) }}"
           class="sec-tab {{ $filter === $key ? 'active' : '' }}">
            {{ $label }}<span class="cnt">({{ $stats[$key === 'all' ? 'total' : $key] }})</span>
        </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('production.section', $stage) }}" class="sec-bar">
        @if($filter !== 'all')<input type="hidden" name="filter" value="{{ $filter }}">@endif
        <input type="text" name="q" placeholder="Search job, customer, number…" value="{{ $search }}">
        <button type="submit" class="sec-btn">Search</button>
        @if($search)
        <a href="{{ route('production.section', ['stage' => $stage, 'filter' => $filter !== 'all' ? $filter : null]) }}" class="sec-btn outline">Clear</a>
        @endif
        @if($isAdmin)
        <a href="{{ route('production.start-job') }}" class="sec-btn outline" style="margin-left:auto;">+ Start Job</a>
        @endif
    </form>

    <div class="sec-list">
        @forelse($jobs as $job)
        @php
            $record = $openStages->get($job->id);
            if (! $record || ! $record->task_started_at) {
                $status = 'new'; $statusLabel = 'Not Started';
            } elseif (! $record->task_ended_at) {
                $status = 'progress'; $statusLabel = 'In Progress';
            } else {
                $status = 'ready'; $statusLabel = 'Task Done';
            }
            $priColor = \App\ProductionJob::priorityColor($job->priority);
            $isOverdue = $job->due_date && $job->due_date->isPast();
            $plan = $job->sectionPlans->firstWhere('stage', $stage);
            $stageTasks = $job->tasks->where('stage', $stage);
            $stageAssignees = $job->assignments->where('stage', $stage);
            $hasSectionPlan = ($plan && ($plan->estimated_minutes || $plan->notes)) || $stageTasks->count() || $stageAssignees->count();
        @endphp
        <div class="sec-job" data-job-id="{{ $job->id }}" data-status="{{ $status }}">
            <div class="sec-job-head">
                <div style="flex:1;min-width:0;">
                    <div class="sec-job-num">{{ $job->job_number }}</div>
                    <div class="sec-job-title">{{ $job->title }}</div>
                    <div class="sec-job-meta">
                        <span>👤 {{ $job->customer_name }}</span>
                        @if($job->customer_phone)<span>📱 {{ $job->customer_phone }}</span>@endif
                        @if($job->due_date)
                        <span style="{{ $isOverdue ? 'color:#ef4444;font-weight:700;' : '' }}">📅 {{ $job->due_date->format('d M Y') }}</span>
                        @endif
                        @if($plan && $plan->estimated_minutes)
                        <span>⏱ Est. {{ $plan->formattedEstimate() }}</span>
                        @endif
                        @if($stageTasks->count())
                        <span>📋 {{ $stageTasks->count() }} task{{ $stageTasks->count() > 1 ? 's' : '' }}</span>
                        @endif
                        @if($stageAssignees->count())
                        <span title="Assigned for this job">👥 {{ $stageAssignees->count() }} assigned</span>
                        @endif
                        <span>🕐 {{ $job->created_at->format('d M, h:i A') }}</span>
                    </div>
                </div>
                <span class="sec-status {{ $status }}" data-status-badge>{{ $statusLabel }}</span>
            </div>

            @if($hasSectionPlan)
            <div class="sec-job-plan">
                <div class="sec-job-plan-title">Work for {{ $stageLabel }}</div>
                @if($plan && $plan->estimated_minutes)
                <div class="sec-plan-est">⏱ Section estimate: {{ $plan->formattedEstimate() }}</div>
                @endif
                @if($plan && $plan->notes)
                <div class="sec-plan-notes">{{ $plan->notes }}</div>
                @endif
                @if($stageAssignees->count())
                <div class="sec-plan-team">
                    @foreach($stageAssignees as $a)
                    <span>{{ \App\ProductionJobAssignment::userDisplayName($a->user) }}</span>
                    @endforeach
                </div>
                @endif
                @if($stageTasks->count())
                <ul class="sec-task-list">
                    @foreach($stageTasks as $t)
                    <li>
                        <span>{{ $t->title }}</span>
                        @if($t->formattedEstimate())
                        <span class="sec-task-time">{{ $t->formattedEstimate() }}</span>
                        @else
                        <span class="sec-task-time" style="opacity:.45;font-weight:600;">—</span>
                        @endif
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
            @endif

            @if($job->description || $job->google_drive_url || $job->files->count())
            <div class="sec-job-body">
                @if($job->description)
                <div class="sec-job-desc">{{ $job->description }}</div>
                @endif
                <div class="sec-tags">
                    <span class="sec-tag" style="background:{{ $priColor }}22;color:{{ $priColor }};">{{ ucfirst($job->priority) }}</span>
                    @if($job->google_drive_url)
                    <a href="{{ $job->google_drive_url }}" target="_blank" class="sec-tag" style="background:#dcfce7;color:#15803d;text-decoration:none;">Google Drive ↗</a>
                    @endif
                    @if($job->files->count())
                    <span class="sec-tag" style="background:#e0e7ff;color:#4f46e5;">📎 {{ $job->files->count() }} files</span>
                    @endif
                </div>
            </div>
            @endif

            <div class="sec-job-foot">
                <a href="{{ route('production.show', $job) }}" class="sec-action sec-action-view">Open Job →</a>

                @php $jobPending = isset($pendingApprovals) ? $pendingApprovals->get($job->id) : null; @endphp
                @if($jobPending)
                <span class="sec-action" style="background:#fef3c7;color:#92400e;cursor:default;">⏳ Awaiting Manager Approval</span>
                @elseif($status === 'new')
                <button type="button" class="sec-action sec-action-start" onclick="beginStart({{ $job->id }}, this)">▶ Start Task</button>
                @elseif($status === 'progress')
                <button type="button" class="sec-action sec-action-end" onclick="endTask({{ $job->id }}, this)">⏹ End Task</button>
                @elseif($nextStage)
                <button type="button" class="sec-action sec-action-move" onclick="moveJob({{ $job->id }}, this)">
                    → {{ !empty($isProductionManager) ? 'Move to' : 'Request Move to' }} {{ $nextStageLabel }}
                </button>
                @endif
            </div>
        </div>
        @empty
        <div class="sec-empty">
            <div style="font-size:32px;margin-bottom:8px;">📭</div>
            <strong>No jobs in this section</strong>
            <div style="margin-top:6px;font-size:12px;">New jobs will appear here when they arrive in {{ $stageLabel }}.</div>
        </div>
        @endforelse
    </div>
</div>

<div id="secToast" class="sec-toast"></div>

@if($prevStage)
<div class="sec-modal-ov" id="rateModal">
    <div class="sec-modal">
        <div class="sec-modal-head">
            <h3>Rate incoming work</h3>
            <p>Rate each <strong>{{ $prevStageLabel }}</strong> team member for the work you received.</p>
        </div>
        <div class="sec-modal-body">
            @if($prevStageEmployees->isEmpty())
            <div class="sec-rate-empty">No team members found in {{ $prevStageLabel }} to rate. You can start the task directly.</div>
            @else
            <div class="sec-modal-intro">Give each person a star rating (optional). Add a comment if you like.</div>
            @foreach($prevStageEmployees as $emp)
            <div class="sec-rate-emp" data-user-id="{{ $emp['user_id'] }}">
                <div class="sec-rate-emp-top">
                    <div class="sec-rate-av">{{ $emp['initials'] }}</div>
                    <div class="sec-rate-name">{{ $emp['name'] }}</div>
                    @if($emp['is_head'])<span class="sec-rate-head-tag">Head</span>@endif
                </div>
                <div class="sec-stars" data-stars>
                    @for($i = 1; $i <= 5; $i++)
                    <span class="sec-star" data-val="{{ $i }}">&#9733;</span>
                    @endfor
                </div>
                <textarea data-comment placeholder="Comment for {{ $emp['name'] }} (optional)"></textarea>
            </div>
            @endforeach
            @endif
        </div>
        <div class="sec-modal-foot">
            <button type="button" class="sec-modal-btn cancel" id="rateSkip">Skip &amp; Start</button>
            <button type="button" class="sec-modal-btn confirm" id="rateConfirm">Save &amp; Start Task</button>
        </div>
    </div>
</div>
@endif
@endsection

@section('javascript')
<script>
const CSRF = '{{ csrf_token() }}';
const TASK_START = '{{ url('production') }}';
const NEXT_STAGE = @json($nextStageLabel);
const HAS_PREV_STAGE = @json((bool) $prevStage);

function toast(msg, isError) {
    const t = document.getElementById('secToast');
    t.textContent = msg;
    t.style.background = isError ? '#ef4444' : '#111827';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2800);
}

function updateJobRow(jobId, newStatus) {
    const row = document.querySelector(`.sec-job[data-job-id="${jobId}"]`);
    if (!row) return;
    row.dataset.status = newStatus;
    const badge = row.querySelector('[data-status-badge]');
    const foot = row.querySelector('.sec-job-foot');
    const labels = { new: 'Not Started', progress: 'In Progress', ready: 'Task Done' };
    if (badge) {
        badge.className = 'sec-status ' + newStatus;
        badge.textContent = labels[newStatus] || newStatus;
    }
    if (!foot) return;
    const viewBtn = foot.querySelector('.sec-action-view');
    foot.innerHTML = '';
    if (viewBtn) foot.appendChild(viewBtn);
    if (newStatus === 'new') {
        foot.insertAdjacentHTML('beforeend', `<button type="button" class="sec-action sec-action-start" onclick="beginStart(${jobId}, this)">▶ Start Task</button>`);
    } else if (newStatus === 'progress') {
        foot.insertAdjacentHTML('beforeend', `<button type="button" class="sec-action sec-action-end" onclick="endTask(${jobId}, this)">⏹ End Task</button>`);
    } else if (@json((bool) $nextStage)) {
        const moveLabel = {{ !empty($isProductionManager) ? 'true' : 'false' }} ? 'Move to' : 'Request Move to';
        foot.insertAdjacentHTML('beforeend', `<button type="button" class="sec-action sec-action-move" onclick="moveJob(${jobId}, this)">→ ${moveLabel} ${NEXT_STAGE}</button>`);
    }
}

// ── Incoming-work rating modal ──────────────────────────────────────────
let rateJobId = null;
let rateBtn = null;

const rateModal = document.getElementById('rateModal');

// Move modal to <body> so position:fixed centers against the viewport
// (a transformed ancestor would otherwise offset it).
if (rateModal && rateModal.parentElement !== document.body) {
    document.body.appendChild(rateModal);
}

function beginStart(jobId, btn) {
    // First section (no previous work) → start immediately.
    if (!HAS_PREV_STAGE || !rateModal) { startTask(jobId, btn); return; }

    rateJobId = jobId;
    rateBtn = btn;

    // Reset all employee rows.
    rateModal.querySelectorAll('.sec-rate-emp').forEach(row => {
        row.dataset.rating = '';
        row.querySelectorAll('.sec-star').forEach(s => s.classList.remove('on'));
        const cmt = row.querySelector('[data-comment]');
        if (cmt) cmt.value = '';
    });

    rateModal.classList.add('show');
}

function closeRateModal() {
    rateModal?.classList.remove('show');
}

function collectRatings() {
    const ratings = [];
    rateModal.querySelectorAll('.sec-rate-emp').forEach(row => {
        const rating = parseInt(row.dataset.rating || '0', 10);
        if (!rating) return;
        const comment = row.querySelector('[data-comment]')?.value.trim() || '';
        ratings.push({
            rated_user_id: parseInt(row.dataset.userId, 10),
            rating: rating,
            comment: comment
        });
    });
    return ratings;
}

if (rateModal) {
    rateModal.querySelectorAll('.sec-rate-emp').forEach(row => {
        const stars = row.querySelectorAll('.sec-star');
        const paint = val => stars.forEach(s => s.classList.toggle('on', parseInt(s.dataset.val, 10) <= val));
        stars.forEach(star => {
            star.addEventListener('mouseenter', () => paint(parseInt(star.dataset.val, 10)));
            star.addEventListener('mouseleave', () => paint(parseInt(row.dataset.rating || '0', 10)));
            star.addEventListener('click', () => {
                const val = parseInt(star.dataset.val, 10);
                // Tap same star again to clear.
                row.dataset.rating = (parseInt(row.dataset.rating || '0', 10) === val) ? '' : String(val);
                paint(parseInt(row.dataset.rating || '0', 10));
            });
        });
    });

    rateModal.addEventListener('click', function (e) {
        if (e.target === this) closeRateModal();
    });

    document.getElementById('rateSkip')?.addEventListener('click', () => {
        closeRateModal();
        startTask(rateJobId, rateBtn);
    });

    document.getElementById('rateConfirm')?.addEventListener('click', () => {
        const ratings = collectRatings();
        closeRateModal();
        startTask(rateJobId, rateBtn, ratings);
    });
}

function startTask(jobId, btn, ratings) {
    if (btn) btn.disabled = true;
    const payload = {};
    if (ratings && ratings.length) payload.ratings = ratings;

    fetch(`${TASK_START}/${jobId}/task/start`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) { toast(d.message || 'Failed.', true); if (btn) btn.disabled = false; return; }
        toast(d.rated ? 'Ratings saved — task started!' : 'Task started!');
        updateJobRow(jobId, 'progress');
    })
    .catch(() => { toast('Request failed.', true); if (btn) btn.disabled = false; });
}

function endTask(jobId, btn) {
    btn.disabled = true;
    fetch(`${TASK_START}/${jobId}/task/end`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) { toast(d.message || 'Failed.', true); btn.disabled = false; return; }
        toast('Task completed!');
        updateJobRow(jobId, 'ready');
    })
    .catch(() => { toast('Request failed.', true); btn.disabled = false; });
}

function moveJob(jobId, btn) {
    const isManager = {{ !empty($isProductionManager) ? 'true' : 'false' }};
    const msg = isManager
        ? ('Move this job to ' + NEXT_STAGE + '?')
        : ('Send move request to Production Manager for ' + NEXT_STAGE + '?');
    if (!confirm(msg)) return;
    btn.disabled = true;
    fetch(`${TASK_START}/${jobId}/advance`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ notes: 'Moved from section dashboard.' })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) { toast(d.message || 'Failed.', true); btn.disabled = false; return; }
        if (d.pending) {
            toast(d.message || 'Request sent to Production Manager');
            setTimeout(() => location.reload(), 800);
            return;
        }
        toast('Job moved to ' + (d.stage_label || 'next section') + '!');
        document.querySelector(`.sec-job[data-job-id="${jobId}"]`)?.remove();
        const list = document.querySelector('.sec-list');
        if (list && !list.querySelector('.sec-job')) {
            list.innerHTML = '<div class="sec-empty"><div style="font-size:32px;margin-bottom:8px;">📭</div><strong>No jobs in this filter</strong></div>';
        }
    })
    .catch(() => { toast('Request failed.', true); btn.disabled = false; });
}
</script>
@endsection
