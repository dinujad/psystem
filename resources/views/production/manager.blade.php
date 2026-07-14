@extends('layouts.app')
@section('title', 'Production Manager Dashboard')

@section('css')
<style>
.pm-page { padding: 0 20px 60px; max-width: 1100px; margin: 0 auto; }
.pm-hero {
    border-radius: 16px; padding: 24px 28px; margin: 18px 0;
    color: #fff !important;
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 55%, #2563eb 100%);
    box-shadow: 0 4px 20px rgba(15,23,42,.25);
}
.pm-hero h1 { font-size: 22px; font-weight: 800; margin: 0 0 6px; color: #fff !important; }
.pm-hero p { margin: 0; font-size: 13px; color: #fff !important; opacity: .9; }
.pm-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
.pm-tab {
    padding: 8px 14px; border-radius: 20px; font-size: 12px; font-weight: 700;
    text-decoration: none; background: #fff; border: 1px solid #e5e7eb; color: #374151;
}
.pm-tab:hover { text-decoration: none; border-color: #2563eb; color: #2563eb; }
.pm-tab.active { background: #2563eb; border-color: #2563eb; color: #fff; }
.pm-tab .cnt { opacity: .85; margin-left: 4px; }
.pm-card {
    background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
    padding: 16px 18px; margin-bottom: 12px;
}
.pm-card-top { display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: flex-start; }
.pm-job-num { font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; }
.pm-title { font-size: 16px; font-weight: 800; color: #111827; margin: 2px 0 6px; }
.pm-meta { font-size: 12px; color: #6b7280; display: flex; flex-wrap: wrap; gap: 10px; }
.pm-arrow {
    display: inline-flex; align-items: center; gap: 8px; margin-top: 10px;
    font-size: 13px; font-weight: 700; color: #111827;
}
.pm-badge {
    font-size: 11px; font-weight: 800; padding: 4px 12px; border-radius: 20px;
    text-transform: uppercase; letter-spacing: .04em;
}
.pm-badge.pending { background: #fef3c7; color: #b45309; }
.pm-badge.approved { background: #dcfce7; color: #15803d; }
.pm-badge.rejected { background: #fee2e2; color: #b91c1c; }
.pm-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
.pm-btn {
    border: none; border-radius: 8px; padding: 8px 14px; font-size: 12px;
    font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center;
}
.pm-btn-view { background: #f3f4f6; color: #374151; }
.pm-btn-approve { background: #10b981; color: #fff; }
.pm-btn-reject { background: #ef4444; color: #fff; }
.pm-notes { margin-top: 10px; font-size: 12px; color: #4b5563; background: #f9fafb; border-radius: 8px; padding: 10px 12px; }
.pm-empty { text-align: center; padding: 48px 20px; color: #6b7280; background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; }
.pm-toast {
    position: fixed; bottom: 24px; right: 24px; background: #111827; color: #fff;
    padding: 12px 18px; border-radius: 10px; font-size: 13px; font-weight: 600;
    opacity: 0; pointer-events: none; transition: opacity .2s; z-index: 9999;
}
.pm-toast.show { opacity: 1; }
.pm-type-tabs { display:flex; gap:8px; margin-bottom:14px; }
.pm-type-tab {
    flex:1; text-align:center; padding:12px; border-radius:12px; font-weight:800; font-size:13px;
    text-decoration:none; border:1.5px solid #e5e7eb; background:#fff; color:#374151;
}
.pm-type-tab.active { border-color:#2563eb; background:#eff6ff; color:#1d4ed8; }
.pm-type-tab:hover { text-decoration:none; }
</style>
@endsection

@section('content')
@php
    $tab = $tab ?? 'moves';
    $movePending = $moveCounts['pending'] ?? 0;
    $matPending = $materialCounts['pending'] ?? 0;
@endphp
<div class="pm-page">
    <div class="pm-hero">
        <h1>Production Manager Dashboard</h1>
        <p>Approve section moves and Workshop raw-material requests.</p>
    </div>

    <div class="pm-type-tabs">
        <a href="{{ route('production.manager', ['tab' => 'moves', 'filter' => $filter]) }}" class="pm-type-tab {{ $tab === 'moves' ? 'active' : '' }}">
            Section Moves {{ $movePending > 0 ? '('.$movePending.')' : '' }}
        </a>
        <a href="{{ route('production.manager', ['tab' => 'materials', 'filter' => $filter]) }}" class="pm-type-tab {{ $tab === 'materials' ? 'active' : '' }}">
            Material Requests {{ $matPending > 0 ? '('.$matPending.')' : '' }}
        </a>
    </div>

    <div class="pm-tabs">
        @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label)
        <a href="{{ route('production.manager', ['tab' => $tab, 'filter' => $key]) }}" class="pm-tab {{ $filter === $key ? 'active' : '' }}">
            {{ $label }} <span class="cnt">{{ $counts[$key] ?? 0 }}</span>
        </a>
        @endforeach
    </div>

    @if($tab === 'materials')
        @forelse($materialRequests as $req)
            @php
                $job = $req->job;
                $mat = $req->material;
                $requester = $req->requester;
                $reqName = $requester
                    ? trim(($requester->surname ?? '').' '.($requester->first_name ?? '').' '.($requester->last_name ?? ''))
                    : '—';
                $reqName = trim($reqName) !== '' ? $reqName : ($requester->username ?? 'User');
                $unit = $mat?->unit?->abbreviation ?? '';
            @endphp
            <div class="pm-card">
                <div class="pm-card-top">
                    <div>
                        <div class="pm-job-num">{{ $job->job_number ?? 'Job #'.$req->job_id }} · Workshop material</div>
                        <div class="pm-title">{{ $mat->name ?? 'Material' }}</div>
                        <div class="pm-meta">
                            <span>Qty: <strong>{{ rtrim(rtrim(number_format((float)$req->quantity, 4, '.', ''), '0'), '.') }} {{ $unit }}</strong></span>
                            <span>Job: {{ $job->title ?? '—' }}</span>
                            <span>👤 {{ $job->customer_name ?? '—' }}</span>
                            <span>Requested by {{ $reqName }}</span>
                            <span>{{ $req->created_at?->format('d M Y, h:i A') }}</span>
                        </div>
                        @if($req->notes)
                        <div class="pm-notes"><strong>Notes:</strong> {{ $req->notes }}</div>
                        @endif
                        @if($req->review_notes && $req->status !== 'pending')
                        <div class="pm-notes"><strong>Manager note:</strong> {{ $req->review_notes }}</div>
                        @endif
                    </div>
                    <span class="pm-badge {{ $req->status }}">{{ $req->status }}</span>
                </div>
                <div class="pm-actions">
                    @if($job)
                    <a href="{{ route('production.show', $job) }}" class="pm-btn pm-btn-view">Open Job</a>
                    @endif
                    @if($req->status === 'pending')
                    <button type="button" class="pm-btn pm-btn-approve" onclick="reviewMat({{ $req->id }}, 'approve', this)">Approve &amp; Issue</button>
                    <button type="button" class="pm-btn pm-btn-reject" onclick="reviewMat({{ $req->id }}, 'reject', this)">Reject</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="pm-empty">
                <div style="font-size:32px;margin-bottom:8px;">📦</div>
                <strong>No {{ $filter === 'all' ? '' : $filter.' ' }}material requests</strong>
                <div style="margin-top:6px;font-size:12px;">Workshop team requests for raw materials will show here.</div>
            </div>
        @endforelse
        <div style="margin-top:16px;">{{ $materialRequests instanceof \Illuminate\Contracts\Pagination\Paginator ? $materialRequests->links() : '' }}</div>
    @else
        @forelse($requests as $req)
            @php
                $job = $req->job;
                $fromLabel = $stages[$req->from_stage] ?? ucfirst($req->from_stage);
                $toLabel = $stages[$req->to_stage] ?? ucfirst($req->to_stage);
                $requester = $req->requester;
                $reqName = $requester
                    ? trim(($requester->surname ?? '').' '.($requester->first_name ?? '').' '.($requester->last_name ?? ''))
                    : '—';
                $reqName = trim($reqName) !== '' ? $reqName : ($requester->username ?? 'User');
            @endphp
            <div class="pm-card" id="approval-{{ $req->id }}">
                <div class="pm-card-top">
                    <div>
                        <div class="pm-job-num">{{ $job->job_number ?? 'Job #'.$req->job_id }}</div>
                        <div class="pm-title">{{ $job->title ?? 'Production job' }}</div>
                        <div class="pm-meta">
                            <span>👤 {{ $job->customer_name ?? '—' }}</span>
                            <span>Requested by {{ $reqName }}</span>
                            <span>{{ $req->created_at?->format('d M Y, h:i A') }}</span>
                        </div>
                        <div class="pm-arrow">
                            <span style="background:#ede9fe;color:#5b3fd9;padding:4px 10px;border-radius:8px;">{{ $fromLabel }}</span>
                            →
                            <span style="background:#dbeafe;color:#1d4ed8;padding:4px 10px;border-radius:8px;">{{ $toLabel }}</span>
                        </div>
                        @if($req->notes)
                        <div class="pm-notes"><strong>Handover notes:</strong> {{ $req->notes }}</div>
                        @endif
                        @if($req->review_notes && $req->status !== 'pending')
                        <div class="pm-notes"><strong>Manager note:</strong> {{ $req->review_notes }}</div>
                        @endif
                    </div>
                    <span class="pm-badge {{ $req->status }}">{{ $req->status }}</span>
                </div>

                <div class="pm-actions">
                    @if($job)
                    <a href="{{ route('production.show', $job) }}" class="pm-btn pm-btn-view">Open Job</a>
                    @endif
                    @if($req->status === 'pending')
                    <button type="button" class="pm-btn pm-btn-approve" onclick="reviewMove({{ $req->id }}, 'approve', this)">Approve &amp; Move</button>
                    <button type="button" class="pm-btn pm-btn-reject" onclick="reviewMove({{ $req->id }}, 'reject', this)">Reject</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="pm-empty">
                <div style="font-size:32px;margin-bottom:8px;">✅</div>
                <strong>No {{ $filter === 'all' ? '' : $filter.' ' }}move requests</strong>
                <div style="margin-top:6px;font-size:12px;">When a section requests a move, it will show up here.</div>
            </div>
        @endforelse
        <div style="margin-top:16px;">{{ $requests instanceof \Illuminate\Contracts\Pagination\Paginator ? $requests->links() : '' }}</div>
    @endif
</div>

<div id="pmToast" class="pm-toast"></div>
@endsection

@section('javascript')
<script>
const CSRF = '{{ csrf_token() }}';
const MOVE_BASE = '{{ url('production/approvals') }}';
const MAT_BASE = '{{ url('production/material-requests') }}';

function toast(msg, isError) {
    const t = document.getElementById('pmToast');
    t.textContent = msg;
    t.style.background = isError ? '#ef4444' : '#111827';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2800);
}

function reviewMove(id, action, btn) {
    const note = prompt(action === 'reject' ? 'Rejection note (optional):' : 'Approval note (optional):', '');
    if (note === null) return;
    btn.disabled = true;
    fetch(`${MOVE_BASE}/${id}/${action}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ review_notes: note || null })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) { toast(d.message || 'Failed.', true); btn.disabled = false; return; }
        toast(d.message || 'Done');
        setTimeout(() => location.reload(), 700);
    })
    .catch(() => { toast('Request failed.', true); btn.disabled = false; });
}

function reviewMat(id, action, btn) {
    const note = prompt(action === 'reject' ? 'Rejection note (optional):' : 'Approval note (optional):', '');
    if (note === null) return;
    btn.disabled = true;
    fetch(`${MAT_BASE}/${id}/${action}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ review_notes: note || null })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) { toast(d.message || 'Failed.', true); btn.disabled = false; return; }
        toast(d.message || 'Done');
        setTimeout(() => location.reload(), 700);
    })
    .catch(() => { toast('Request failed.', true); btn.disabled = false; });
}
</script>
@endsection
