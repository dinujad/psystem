@extends('layouts.app')
@section('title', 'Inquiry — ' . ($inquiry->customer_name ?: $inquiry->phone_number))

@section('css')
<style>
.wi-page { padding: 0 20px 40px; max-width: 1100px; }
.wi-header { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
.wi-title { font-size: 22px; font-weight: 700; color: #111827 !important; margin: 0; }
.wi-sub { font-size: 13px; color: #667781 !important; margin: 4px 0 0; font-family: monospace; }
.wi-actions { display: flex; flex-wrap: wrap; gap: 8px; }
.wi-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; }
.wi-btn-inbox { background: #25d366; color: #fff !important; }
.wi-btn-update { background: #7c5cfc; color: #fff !important; }
.wi-btn-back { background: #f3f4f6; color: #374151 !important; }

.wi-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
@media (max-width: 768px) { .wi-grid { grid-template-columns: 1fr; } }

.wi-card { background: #fff; border: 1px solid #e9edef; border-radius: 12px; padding: 18px 20px; }
.wi-card h3 { font-size: 13px; font-weight: 700; color: #667781 !important; text-transform: uppercase; letter-spacing: .04em; margin: 0 0 14px; }
.wi-row { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 13px; }
.wi-row:last-child { border-bottom: none; }
.wi-row span:first-child { color: #667781 !important; flex-shrink: 0; }
.wi-row span:last-child { color: #111827 !important; font-weight: 500; text-align: right; }

.wi-status-badge { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; color: #fff; }
.wi-cat-pill { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; background: rgba(124,92,252,.12); color: #5b3fd9; }

.wi-timeline { list-style: none; padding: 0; margin: 0; }
.wi-timeline li { position: relative; padding: 0 0 16px 24px; border-left: 2px solid #e9edef; margin-left: 8px; }
.wi-timeline li:last-child { border-left-color: transparent; padding-bottom: 0; }
.wi-timeline li::before { content: ''; position: absolute; left: -7px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background: #7c5cfc; border: 2px solid #fff; box-shadow: 0 0 0 1px #e9edef; }
.wi-tl-title { font-size: 13px; font-weight: 600; color: #111827 !important; }
.wi-tl-meta { font-size: 11.5px; color: #667781 !important; margin-top: 2px; }
.wi-tl-pay { font-size: 12px; color: #10b981 !important; margin-top: 4px; font-weight: 600; }

.wi-chat { max-height: 480px; overflow-y: auto; padding: 12px; background: #e5ddd5; border-radius: 10px; }
.wi-msg { max-width: 75%; margin-bottom: 8px; padding: 8px 12px; border-radius: 8px; font-size: 13px; line-height: 1.45; word-break: break-word; }
.wi-msg.in { background: #fff; color: #111 !important; margin-right: auto; border-top-left-radius: 2px; }
.wi-msg.out { background: #d9fdd3; color: #111 !important; margin-left: auto; border-top-right-radius: 2px; }
.wi-msg-time { font-size: 10px; color: #667781 !important; margin-top: 4px; text-align: right; }
.wi-msg-media { font-size: 12px; color: #7c5cfc !important; margin-top: 4px; }

/* Modal (same light-theme fix as reports) */
.wr-modal-overlay { display: none; position: fixed; inset: 0; z-index: 9000; background: rgba(0,0,0,.45); align-items: center; justify-content: center; color-scheme: light; }
.wr-modal { background: #fff !important; color: #111827 !important; border-radius: 16px; width: 100%; max-width: 500px; padding: 24px; margin: 16px; box-shadow: 0 20px 60px rgba(0,0,0,.2); color-scheme: light; }
.wr-modal h4 { color: #111827 !important; margin: 0 0 4px; font-size: 16px; font-weight: 700; }
.wr-field label { color: #374151 !important; display: block; font-size: 12px; font-weight: 600; margin-bottom: 5px; }
.wr-field input, .wr-field select, .wr-field textarea { width: 100%; background: #fff !important; color: #111827 !important; border: 1.5px solid #e5e7eb !important; border-radius: 8px; padding: 9px 12px; font-size: 13px; box-sizing: border-box; }
.wr-field select option { background: #fff !important; color: #111 !important; }
.wr-field { margin-bottom: 14px; }
.wr-pay-fields { display: none; background: #f0fdf4 !important; border: 1px solid #bbf7d0; border-radius: 10px; padding: 14px; margin-bottom: 14px; }
.wr-pay-fields.show { display: block; }
#sm-prod-fields { background: #ede9fe !important; border-color: #c4b5fd !important; }
.wr-modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 8px; }
.wr-modal-cancel { border: 1.5px solid #e5e7eb; background: #fff !important; color: #374151 !important; border-radius: 8px; padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer; }
.wr-modal-save { background: #7c5cfc !important; color: #fff !important; border: none; border-radius: 8px; padding: 9px 20px; font-size: 13px; font-weight: 600; cursor: pointer; }
.wr-error { color: #ef4444 !important; font-size: 12.5px; min-height: 18px; }
</style>
@endsection

@section('content')
@php
    $statusKey   = $inquiry->inquiry_status ?: 'quotation_waiting';
    $statusLabel = $statuses[$statusKey] ?? $statusKey;
    $statusColor = \App\Http\Controllers\WhatsappAgentController::statusBadgeColor($statusKey);
    $agentName   = $inquiry->agent ? \App\Http\Controllers\WhatsappAgentController::agentDisplayName($inquiry->agent) : '—';
    $closedName  = $inquiry->closedBy ? \App\Http\Controllers\WhatsappAgentController::agentDisplayName($inquiry->closedBy) : '—';
    $updatedName = $inquiry->statusUpdatedBy ? \App\Http\Controllers\WhatsappAgentController::agentDisplayName($inquiry->statusUpdatedBy) : '—';
@endphp

<div class="content-header">
    <h1>Inquiry Details</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('home') }}">Home</a></li>
        <li><a href="{{ route('admin.whatsapp.reports') }}">Inquiry Reports</a></li>
        <li class="active">{{ $inquiry->customer_name ?: $inquiry->phone_number }}</li>
    </ol>
</div>

<div class="content wi-page">

    <div class="wi-header">
        <div>
            <h2 class="wi-title">{{ $inquiry->customer_name ?: 'Unknown Customer' }}</h2>
            <p class="wi-sub">+{{ $inquiry->phone_number }}</p>
            <div style="margin-top:10px;">
                <span class="wi-status-badge" id="page-status-badge" style="background:{{ $statusColor }}">{{ $statusLabel }}</span>
                <span class="wi-cat-pill" style="margin-left:8px;">{{ $inquiry->inquiry_category }}</span>
            </div>
        </div>
        <div class="wi-actions">
            <a href="{{ route('admin.whatsapp.reports') }}" class="wi-btn wi-btn-back">← Back to Reports</a>
            <a href="{{ route('whatsapp.inbox', ['phone' => $inquiry->phone_number]) }}" class="wi-btn wi-btn-inbox">
                💬 Open in WhatsApp Inbox
            </a>
            <button type="button" class="wi-btn wi-btn-update" onclick="openStatusModal()">Update Status</button>
        </div>
    </div>

    <div class="wi-grid">
        <div class="wi-card">
            <h3>Inquiry Info</h3>
            <div class="wi-row"><span>Customer</span><span>{{ $inquiry->customer_name ?: '—' }}</span></div>
            <div class="wi-row"><span>Phone</span><span>+{{ $inquiry->phone_number }}</span></div>
            <div class="wi-row"><span>Category</span><span>{{ $inquiry->inquiry_category }}</span></div>
            <div class="wi-row"><span>Notes</span><span>{{ $inquiry->inquiry_notes ?: '—' }}</span></div>
            <div class="wi-row"><span>Closed</span><span>{{ $inquiry->closed_at ? $inquiry->closed_at->format('d M Y, H:i') : '—' }}</span></div>
        </div>

        <div class="wi-card">
            <h3>People &amp; Payment</h3>
            <div class="wi-row"><span>Handled by</span><span>{{ $agentName }}</span></div>
            <div class="wi-row"><span>Closed by</span><span>{{ $closedName }}</span></div>
            <div class="wi-row"><span>Last status update</span><span>{{ $updatedName }}@if($inquiry->status_updated_at) · {{ $inquiry->status_updated_at->format('d M Y, H:i') }}@endif</span></div>
            @if($inquiry->payment_amount)
            <div class="wi-row"><span>Payment</span><span style="color:#10b981 !important;">Rs {{ number_format($inquiry->payment_amount, 2) }}</span></div>
            <div class="wi-row"><span>Method</span><span>{{ $payMethods[$inquiry->payment_method] ?? $inquiry->payment_method }}</span></div>
            @if($inquiry->payment_reference)
            <div class="wi-row"><span>Reference</span><span>{{ $inquiry->payment_reference }}</span></div>
            @endif
            @else
            <div class="wi-row"><span>Payment</span><span style="color:#aaa;">—</span></div>
            @endif
        </div>
    </div>

    <div class="wi-grid">
        <div class="wi-card">
            <h3>Status History</h3>
            <ul class="wi-timeline">
                @forelse($inquiry->statusLogs as $log)
                <li>
                    <div class="wi-tl-title">
                        @if($log->from_status)
                            {{ $statuses[$log->from_status] ?? $log->from_status }} →
                        @endif
                        {{ $statuses[$log->to_status] ?? $log->to_status }}
                    </div>
                    @if($log->payment_amount)
                    <div class="wi-tl-pay">Rs {{ number_format($log->payment_amount, 2) }} · {{ $payMethods[$log->payment_method] ?? $log->payment_method }}@if($log->payment_reference) · Ref: {{ $log->payment_reference }}@endif</div>
                    @endif
                    @if($log->notes)<div class="wi-tl-meta">{{ $log->notes }}</div>@endif
                    <div class="wi-tl-meta">{{ $log->updatedBy ? \App\Http\Controllers\WhatsappAgentController::agentDisplayName($log->updatedBy) : '—' }} · {{ $log->created_at->format('d M Y, H:i') }}</div>
                </li>
                @empty
                <li style="border:none;padding-left:0;color:#aaa;">No status history.</li>
                @endforelse
            </ul>
        </div>

        <div class="wi-card">
            <h3>WhatsApp Chat History ({{ $messages->count() }} messages)</h3>
            <div class="wi-chat">
                @forelse($messages as $msg)
                <div class="wi-msg {{ $msg->direction === 'outbound' ? 'out' : 'in' }}">
                    @if($msg->message){!! nl2br(e($msg->message)) !!}@endif
                    @if($msg->media_path)
                    <div class="wi-msg-media">📎 {{ $msg->media_filename ?: $msg->media_type }}
                        @if($msg->media_url) — <a href="{{ $msg->media_url }}" target="_blank" style="color:#7c5cfc;">View</a>@endif
                    </div>
                    @endif
                    <div class="wi-msg-time">{{ $msg->created_at->format('d M Y, H:i') }} · {{ $msg->direction === 'outbound' ? 'Sent' : 'Received' }}</div>
                </div>
                @empty
                <p style="text-align:center;color:#667781;padding:24px;">No messages found for this number.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Status update modal --}}
<div class="wr-modal-overlay" id="status-modal">
    <div class="wr-modal">
        <h4>Update Inquiry Status</h4>
        <p style="font-size:12.5px;color:#667781;margin:0 0 16px;">{{ $inquiry->customer_name }}</p>
        <div class="wr-field">
            <label>Status *</label>
            <select id="sm-status" onchange="toggleStatusFields()">
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" {{ $statusKey === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="wr-pay-fields {{ $statusKey === 'payment_received' ? 'show' : '' }}" id="sm-pay-fields">
            <div class="wr-field"><label>Payment Amount (Rs) *</label><input type="number" id="sm-amount" step="0.01" value="{{ $inquiry->payment_amount }}"></div>
            <div class="wr-field"><label>Payment Method *</label>
                <select id="sm-method">
                    <option value="">— Select —</option>
                    @foreach($payMethods as $k => $l)
                        <option value="{{ $k }}" {{ $inquiry->payment_method === $k ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="wr-field"><label>Reference Number</label><input type="text" id="sm-reference" value="{{ $inquiry->payment_reference }}"></div>
        </div>
        <div class="wr-pay-fields" id="sm-prod-fields">
            <div class="wr-field"><label>Google Drive Folder URL</label>
                <input type="url" id="sm-drive" placeholder="https://drive.google.com/drive/folders/…">
            </div>
        </div>
        <div class="wr-field"><label>Notes</label><textarea id="sm-notes" rows="2"></textarea></div>
        <p class="wr-error" id="sm-error"></p>
        <div class="wr-modal-actions">
            <button type="button" class="wr-modal-cancel" onclick="document.getElementById('status-modal').style.display='none'">Cancel</button>
            <button type="button" class="wr-modal-save" id="sm-save-btn" onclick="saveStatus()">Save Status</button>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
const INQUIRY_ID = {{ $inquiry->id }};

function toggleStatusFields() {
    const s = document.getElementById('sm-status').value;
    document.getElementById('sm-pay-fields').classList.toggle('show', s === 'payment_received');
    document.getElementById('sm-prod-fields').classList.toggle('show', s === 'sent_to_production');
}
function togglePaymentFields() { toggleStatusFields(); }
function openStatusModal() {
    toggleStatusFields();
    document.getElementById('status-modal').style.display = 'flex';
}
async function saveStatus() {
    const status = document.getElementById('sm-status').value;
    const errEl = document.getElementById('sm-error');
    const btn = document.getElementById('sm-save-btn');
    const body = { inquiry_status: status, status_notes: document.getElementById('sm-notes').value.trim() };
    if (status === 'payment_received') {
        body.payment_amount = document.getElementById('sm-amount').value;
        body.payment_method = document.getElementById('sm-method').value;
        body.payment_reference = document.getElementById('sm-reference').value.trim();
    }
    if (status === 'sent_to_production') {
        const drive = document.getElementById('sm-drive').value.trim();
        if (drive) body.google_drive_url = drive;
    }
    btn.disabled = true;
    const r = await fetch(`/admin/whatsapp/inquiries/${INQUIRY_ID}/status`, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    });
    const d = await r.json();
    btn.disabled = false;
    if (d.success) {
        location.reload();
    } else {
        errEl.textContent = d.message || Object.values(d.errors || {}).flat().join(' ') || 'Failed';
    }
}
</script>
@endsection
