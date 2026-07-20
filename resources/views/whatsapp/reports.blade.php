@extends('layouts.app')
@section('title', 'WhatsApp Inquiry Reports')

@section('css')
<style>
.wr-page { padding: 0 20px 40px; }
.wr-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin: 16px 0; }
.wr-stat-card { background: #fff; border: 1px solid #e9edef; border-radius: 12px; padding: 14px 16px; position: relative; overflow: hidden; }
.wr-stat-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; border-radius: 12px 0 0 12px; }
.wr-stat-card.purple::before { background: #7c5cfc; }
.wr-stat-card.green::before  { background: #25d366; }
.wr-stat-card.blue::before   { background: #3b82f6; }
.wr-stat-card.orange::before { background: #f59e0b; }
.wr-stat-label { font-size: 11px; font-weight: 600; color: #667781; text-transform: uppercase; letter-spacing: .04em; margin: 0; }
.wr-stat-val   { font-size: 22px; font-weight: 700; color: #111; margin: 4px 0 0; }

.wr-filters { background: #fff; border: 1px solid #e9edef; border-radius: 12px; padding: 16px 20px; display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; margin-bottom: 16px; }
.wr-filter-btn { background: #7c5cfc; color: #fff; border: none; border-radius: 8px; padding: 8px 18px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
.wr-filter-btn.reset { background: #f3f4f6; color: #374151; }

.wr-status-section { background: #fff; border: 1px solid #e9edef; border-radius: 12px; padding: 16px 20px; margin-bottom: 16px; }
.wr-status-title { font-size: 14px; font-weight: 700; color: #111; margin: 0 0 12px; }
.wr-status-pills { display: flex; flex-wrap: wrap; gap: 8px; }
.wr-status-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; color: #fff; }
.wr-status-pill .cnt { background: rgba(255,255,255,.25); border-radius: 10px; padding: 0 6px; font-size: 11px; }

.wr-table-wrap { background: #fff; border: 1px solid #e9edef; border-radius: 12px; overflow-x: auto; }
.wr-table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 900px; }
.wr-table thead th { background: #f8f9fa; padding: 10px 12px; font-size: 11px; font-weight: 700; color: #667781; text-transform: uppercase; letter-spacing: .04em; border-bottom: 1px solid #e9edef; text-align: left; white-space: nowrap; }
.wr-table tbody td { padding: 10px 12px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; color: #111; }
.wr-table tbody tr:hover td { background: #faf9ff; }
.wr-cat-pill { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; background: rgba(124,92,252,.12); color: #5b3fd9; }
.wr-agent-pill { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; background: #d9f7e5; color: #075e54; }
.wr-status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; color: #fff; white-space: nowrap; }
.wr-btn-sm { border: none; border-radius: 6px; padding: 5px 10px; font-size: 11.5px; font-weight: 600; cursor: pointer; margin-right: 4px; }
.wr-btn-update { background: #7c5cfc; color: #fff; }
.wr-btn-history { background: #f3f4f6; color: #374151; }
.wr-pay-info { font-size: 11px; color: #54656f; margin-top: 3px; }
.wr-empty { text-align: center; padding: 48px 20px; color: #aaa; }
.wr-pagination { padding: 14px 20px; display: flex; justify-content: flex-end; }

/* Modal — force light theme (overrides dark skin) */
.wr-modal-overlay { display: none; position: fixed; inset: 0; z-index: 9000; background: rgba(0,0,0,.45); align-items: center; justify-content: center; color-scheme: light; }
.wr-modal {
    background: #ffffff !important;
    color: #111827 !important;
    border-radius: 16px;
    width: 100%;
    max-width: 500px;
    padding: 24px;
    margin: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,.2);
    max-height: 90vh;
    overflow-y: auto;
    color-scheme: light;
}
.wr-modal h4 { margin: 0 0 4px; font-size: 16px; font-weight: 700; color: #111827 !important; }
.wr-modal-sub { font-size: 12.5px; color: #667781 !important; margin: 0 0 18px; }
.wr-field { margin-bottom: 14px; }
.wr-field label { display: block; font-size: 12px; font-weight: 600; color: #374151 !important; margin-bottom: 5px; }
.wr-field input,
.wr-field select,
.wr-field textarea {
    width: 100%;
    border: 1.5px solid #e5e7eb !important;
    border-radius: 8px;
    padding: 9px 12px;
    font-size: 13px;
    outline: none;
    box-sizing: border-box;
    background-color: #ffffff !important;
    background-image: none !important;
    color: #111827 !important;
    -webkit-appearance: menulist;
    appearance: auto;
}
.wr-field select option {
    background-color: #ffffff !important;
    color: #111827 !important;
}
.wr-field input::placeholder,
.wr-field textarea::placeholder { color: #9ca3af !important; }
.wr-field input:focus,
.wr-field select:focus,
.wr-field textarea:focus { border-color: #7c5cfc !important; box-shadow: 0 0 0 3px rgba(124,92,252,.15); }
.wr-pay-fields { display: none; background: #f0fdf4 !important; border: 1px solid #bbf7d0; border-radius: 10px; padding: 14px; margin-bottom: 14px; }
.wr-pay-fields.show { display: block; }
.wr-pay-fields label { color: #166534 !important; }
#sm-prod-fields { background: #ede9fe !important; border-color: #c4b5fd !important; }
#sm-prod-fields label { color: #5b21b6 !important; }
.wr-modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 8px; }
.wr-modal-cancel { border: 1.5px solid #e5e7eb; background: #fff !important; color: #374151 !important; border-radius: 8px; padding: 9px 18px; font-size: 13px; font-weight: 600; cursor: pointer; }
.wr-modal-save { background: #7c5cfc !important; color: #fff !important; border: none; border-radius: 8px; padding: 9px 20px; font-size: 13px; font-weight: 600; cursor: pointer; }
.wr-error { color: #ef4444 !important; font-size: 12.5px; min-height: 18px; margin-bottom: 8px; }

.wr-history-list { list-style: none; padding: 0; margin: 0; }
.wr-history-list li { padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-size: 12.5px; color: #374151 !important; }
.wr-history-list li:last-child { border-bottom: none; }
.wr-history-arrow { color: #7c5cfc !important; font-weight: 700; }

/* Filter bar selects — same fix */
.wr-filters select,
.wr-filters input[type=date] {
    border: 1.5px solid #e5e7eb !important;
    border-radius: 8px;
    padding: 7px 10px;
    font-size: 13px;
    outline: none;
    background-color: #ffffff !important;
    color: #111827 !important;
    min-width: 140px;
    color-scheme: light;
}
.wr-filters select option { background: #fff !important; color: #111 !important; }
.wr-filters label { color: #374151 !important; }
</style>
@endsection

@section('content')
<div class="content-header">
    <h1>WhatsApp Inquiry Reports <small>Track inquiry status &amp; payments</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('home') }}">Home</a></li>
        <li><a href="{{ (auth()->user()->can('whatsapp.access') || auth()->user()->can('send_notifications')) ? route('admin.whatsapp.agents.index') : action([\App\Http\Controllers\WhatsappController::class, 'inbox']) }}">WhatsApp</a></li>
        <li class="active">Inquiry Reports</li>
    </ol>
</div>

<div class="content wr-page">

    <div class="wr-stat-grid">
        <div class="wr-stat-card purple">
            <p class="wr-stat-label">Total Inquiries</p>
            <p class="wr-stat-val">{{ number_format($statusStats->sum('total')) }}</p>
        </div>
        <div class="wr-stat-card green">
            <p class="wr-stat-label">Payment Received</p>
            <p class="wr-stat-val">{{ $statusStats->firstWhere('inquiry_status', 'payment_received')->total ?? 0 }}</p>
        </div>
        <div class="wr-stat-card blue">
            <p class="wr-stat-label">In Production</p>
            <p class="wr-stat-val">{{ $statusStats->firstWhere('inquiry_status', 'sent_to_production')->total ?? 0 }}</p>
        </div>
        <div class="wr-stat-card orange">
            <p class="wr-stat-label">Quotation Waiting</p>
            <p class="wr-stat-val">{{ $statusStats->firstWhere('inquiry_status', 'quotation_waiting')->total ?? 0 }}</p>
        </div>
    </div>

    @if($statusStats->count())
    <div class="wr-status-section">
        <p class="wr-status-title">Inquiries by Status</p>
        <div class="wr-status-pills">
            @foreach($statusStats as $ss)
            <span class="wr-status-pill" style="background:{{ \App\Http\Controllers\WhatsappAgentController::statusBadgeColor($ss->inquiry_status) }}">
                {{ $statuses[$ss->inquiry_status] ?? $ss->inquiry_status }}
                <span class="cnt">{{ $ss->total }}</span>
            </span>
            @endforeach
        </div>
    </div>
    @endif

    <form method="GET" action="{{ route('admin.whatsapp.reports') }}" class="wr-filters">
        <div>
            <label>Status</label>
            <select name="inquiry_status">
                <option value="">All statuses</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" {{ request('inquiry_status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Category</label>
            <select name="category">
                <option value="">All categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        @if(auth()->user()->can('whatsapp.access') || auth()->user()->can('send_notifications'))
        <div>
            <label>Agent</label>
            <select name="agent_id">
                <option value="">All agents</option>
                @foreach($agents as $a)
                    <option value="{{ $a->id }}" {{ request('agent_id') == $a->id ? 'selected' : '' }}>
                        {{ trim(($a->first_name ?? '') . ' ' . ($a->last_name ?? '')) ?: $a->username }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif
        <div>
            <label>From</label>
            <input type="date" name="from" value="{{ request('from') }}">
        </div>
        <div>
            <label>To</label>
            <input type="date" name="to" value="{{ request('to') }}">
        </div>
        <button type="submit" class="wr-filter-btn">Filter</button>
        <a href="{{ route('admin.whatsapp.reports') }}" class="wr-filter-btn reset">Reset</a>
    </form>

    <div class="wr-table-wrap">
        @if($records->count())
        <table class="wr-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Agent</th>
                    <th>Closed</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $i => $r)
                @php
                    $agentName = $r->agent ? (trim(($r->agent->first_name ?? '') . ' ' . ($r->agent->last_name ?? '')) ?: $r->agent->username) : '—';
                    $statusKey = $r->inquiry_status ?: 'quotation_waiting';
                    $statusLabel = $statuses[$statusKey] ?? $statusKey;
                    $statusColor = \App\Http\Controllers\WhatsappAgentController::statusBadgeColor($statusKey);
                @endphp
                <tr id="row-{{ $r->id }}">
                    <td style="color:#aaa;font-size:12px;">{{ $records->firstItem() + $i }}</td>
                    <td style="font-weight:600;">
                        <a href="{{ route('admin.whatsapp.inquiries.show', $r->id) }}" style="color:#7c5cfc;text-decoration:none;font-weight:600;">{{ $r->customer_name ?: '—' }}</a>
                    </td>
                    <td style="font-family:monospace;font-size:12px;">{{ $r->phone_number }}</td>
                    <td><span class="wr-cat-pill">{{ $r->inquiry_category ?: '—' }}</span></td>
                    <td>
                        <span class="wr-status-badge" id="badge-{{ $r->id }}" style="background:{{ $statusColor }}">{{ $statusLabel }}</span>
                    </td>
                    <td>
                        @if($r->payment_amount)
                            <div style="font-weight:600;font-size:12.5px;">Rs {{ number_format($r->payment_amount, 2) }}</div>
                            <div class="wr-pay-info">{{ $payMethods[$r->payment_method] ?? $r->payment_method }}@if($r->payment_reference) · Ref: {{ $r->payment_reference }}@endif</div>
                        @else
                            <span style="color:#aaa;">—</span>
                        @endif
                    </td>
                    <td><span class="wr-agent-pill">{{ $agentName }}</span></td>
                    <td style="font-size:12px;color:#667781;white-space:nowrap;">{{ $r->closed_at ? $r->closed_at->format('d M Y') : '—' }}</td>
                    <td style="white-space:nowrap;">
                        <a href="{{ route('admin.whatsapp.inquiries.show', $r->id) }}" class="wr-btn-sm" style="background:#111827;color:#fff;text-decoration:none;display:inline-block;">View</a>
                        <button type="button" class="wr-btn-sm wr-btn-update"
                            onclick="openStatusModal({{ $r->id }}, '{{ $statusKey }}', '{{ addslashes($r->customer_name) }}', {{ $r->payment_amount ?? 'null' }}, '{{ $r->payment_method }}', '{{ addslashes($r->payment_reference ?? '') }}')">
                            Update
                        </button>
                        <button type="button" class="wr-btn-sm wr-btn-history" onclick="openHistoryModal({{ $r->id }}, '{{ addslashes($r->customer_name) }}')">
                            History
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="wr-pagination">{{ $records->links() }}</div>
        @else
        <div class="wr-empty">
            <p style="font-size:14px;">No inquiries found for the selected filters.</p>
        </div>
        @endif
    </div>
</div>

{{-- Status update modal --}}
<div class="wr-modal-overlay" id="status-modal">
    <div class="wr-modal">
        <h4>Update Inquiry Status</h4>
        <p class="wr-modal-sub" id="sm-customer"></p>
        <input type="hidden" id="sm-id">

        <div class="wr-field">
            <label>Status <span style="color:#ef4444">*</span></label>
            <select id="sm-status" onchange="toggleStatusFields()">
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="wr-pay-fields" id="sm-pay-fields">
            <div class="wr-field" style="margin-bottom:10px;">
                <label>Payment Amount (Rs) <span style="color:#ef4444">*</span></label>
                <input type="number" id="sm-amount" step="0.01" min="0" placeholder="0.00">
            </div>
            <div class="wr-field" style="margin-bottom:10px;">
                <label>Payment Method <span style="color:#ef4444">*</span></label>
                <select id="sm-method">
                    <option value="">— Select —</option>
                    @foreach($payMethods as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="wr-field" style="margin-bottom:0;">
                <label>Reference Number</label>
                <input type="text" id="sm-reference" placeholder="e.g. TXN123456">
            </div>
        </div>

        <div class="wr-pay-fields" id="sm-prod-fields">
            <div class="wr-field" style="margin-bottom:0;">
                <label>Google Drive Folder URL</label>
                <input type="url" id="sm-drive" placeholder="https://drive.google.com/drive/folders/…">
                <p style="font-size:11px;color:#667781;margin:6px 0 0;">Logo &amp; design files — sent to Design Team via WhatsApp alert.</p>
            </div>
        </div>

        <div class="wr-field">
            <label>Notes (optional)</label>
            <textarea id="sm-notes" rows="2" placeholder="Any notes about this status change…"></textarea>
        </div>

        <p class="wr-error" id="sm-error"></p>
        <div class="wr-modal-actions">
            <button type="button" class="wr-modal-cancel" onclick="closeStatusModal()">Cancel</button>
            <button type="button" class="wr-modal-save" id="sm-save-btn" onclick="saveStatus()">Save Status</button>
        </div>
    </div>
</div>

{{-- History modal --}}
<div class="wr-modal-overlay" id="history-modal">
    <div class="wr-modal">
        <h4>Status History</h4>
        <p class="wr-modal-sub" id="hm-customer"></p>
        <ul class="wr-history-list" id="hm-list">
            <li style="color:#aaa;">Loading…</li>
        </ul>
        <div class="wr-modal-actions">
            <button type="button" class="wr-modal-cancel" onclick="document.getElementById('history-modal').style.display='none'">Close</button>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
const STATUS_URL = '{{ url('/admin/whatsapp/inquiries') }}';
const STATUS_COLORS = @json(collect($statuses)->mapWithKeys(fn($l, $k) => [$k => \App\Http\Controllers\WhatsappAgentController::statusBadgeColor($k)]));

function toggleStatusFields() {
    const status = document.getElementById('sm-status').value;
    document.getElementById('sm-pay-fields').classList.toggle('show', status === 'payment_received');
    document.getElementById('sm-prod-fields').classList.toggle('show', status === 'sent_to_production');
}
function togglePaymentFields() { toggleStatusFields(); }

function openStatusModal(id, status, customer, amount, method, reference) {
    document.getElementById('sm-id').value = id;
    document.getElementById('sm-customer').textContent = customer || 'Customer';
    document.getElementById('sm-status').value = status || 'quotation_waiting';
    document.getElementById('sm-amount').value = amount || '';
    document.getElementById('sm-method').value = method || '';
    document.getElementById('sm-reference').value = reference || '';
    document.getElementById('sm-notes').value = '';
    document.getElementById('sm-drive').value = '';
    document.getElementById('sm-error').textContent = '';
    toggleStatusFields();
    document.getElementById('status-modal').style.display = 'flex';
}

function closeStatusModal() {
    document.getElementById('status-modal').style.display = 'none';
}

async function saveStatus() {
    const id = document.getElementById('sm-id').value;
    const status = document.getElementById('sm-status').value;
    const errEl = document.getElementById('sm-error');
    const btn = document.getElementById('sm-save-btn');

    const body = {
        inquiry_status: status,
        status_notes: document.getElementById('sm-notes').value.trim(),
    };

    if (status === 'payment_received') {
        body.payment_amount = document.getElementById('sm-amount').value;
        body.payment_method = document.getElementById('sm-method').value;
        body.payment_reference = document.getElementById('sm-reference').value.trim();
        if (!body.payment_amount || parseFloat(body.payment_amount) <= 0) {
            errEl.textContent = 'Payment amount is required.'; return;
        }
        if (!body.payment_method) {
            errEl.textContent = 'Payment method is required.'; return;
        }
    }

    if (status === 'sent_to_production') {
        const drive = document.getElementById('sm-drive').value.trim();
        if (drive) body.google_drive_url = drive;
    }

    errEl.textContent = '';
    btn.disabled = true;
    btn.textContent = 'Saving…';

    try {
        const r = await fetch(`${STATUS_URL}/${id}/status`, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });
        const d = await r.json();
        if (d.success) {
            const badge = document.getElementById('badge-' + id);
            if (badge) {
                badge.textContent = d.status_label;
                badge.style.background = d.status_color;
            }
            closeStatusModal();
            if (status === 'payment_received' || status === 'sent_to_production') location.reload();
        } else {
            errEl.textContent = d.message || 'Failed to update status.';
        }
    } catch (e) {
        errEl.textContent = 'Network error. Please try again.';
    }

    btn.disabled = false;
    btn.textContent = 'Save Status';
}

async function openHistoryModal(id, customer) {
    document.getElementById('hm-customer').textContent = customer || '';
    document.getElementById('hm-list').innerHTML = '<li style="color:#aaa;">Loading…</li>';
    document.getElementById('history-modal').style.display = 'flex';

    const r = await fetch(`${STATUS_URL}/${id}/history`, { headers: { Accept: 'application/json' } });
    const d = await r.json();
    const list = document.getElementById('hm-list');

    if (!d.logs || !d.logs.length) {
        list.innerHTML = '<li style="color:#aaa;">No history yet.</li>';
        return;
    }

    list.innerHTML = d.logs.map(log => {
        let pay = '';
        if (log.payment) pay = `<div style="color:#10b981;margin-top:3px;">Rs ${log.payment} · ${log.method || ''}${log.reference ? ' · Ref: ' + log.reference : ''}</div>`;
        return `<li>
            <span class="wr-history-arrow">${log.from} → ${log.to}</span>
            ${pay}
            ${log.notes ? '<div style="color:#54656f;margin-top:2px;">' + log.notes + '</div>' : ''}
            <div style="color:#aaa;font-size:11px;margin-top:3px;">${log.updated_by} · ${log.at}</div>
        </li>`;
    }).join('');
}
</script>
@endsection
