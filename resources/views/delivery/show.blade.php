@extends('layouts.app')
@section('title', 'Delivery '.$parcel->waybill_no)

@section('css')
<style>
.dlv-page { padding: 0 20px 60px; max-width: 900px; }
.dlv-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin: 20px 0 18px; }
.dlv-title { font-size: 20px; font-weight: 800; color: #1e1b4b; }
.dlv-btn { background: #7c5cfc; color: #fff; border: none; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; }
.dlv-btn:hover { color: #fff; text-decoration: none; }
.dlv-btn.outline { background: #fff; color: #7c5cfc; border: 1.5px solid #7c5cfc; }
.dlv-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; margin-bottom: 16px; }
.dlv-card h3 { font-size: 14px; font-weight: 800; color: #374151; margin: 0 0 14px; text-transform: uppercase; letter-spacing: .04em; }
.dlv-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 720px) { .dlv-grid { grid-template-columns: 1fr; } }
.dlv-item label { display: block; font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase; margin-bottom: 4px; }
.dlv-item div { font-size: 14px; color: #111827; font-weight: 600; }
.dlv-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.dlv-status.pending { background: #fef3c7; color: #92400e; }
.dlv-status.transit { background: #dbeafe; color: #1d4ed8; }
.dlv-status.delivered { background: #dcfce7; color: #15803d; }
.dlv-status.failed { background: #fee2e2; color: #b91c1c; }
.dlv-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; overflow: auto; white-space: pre-wrap; }
.dlv-hook { font-size: 12px; color: #6b7280; word-break: break-all; }
</style>
@endsection

@section('content')
@php $badge = \App\DeliveryParcel::statusBadgeClass($parcel->current_status); @endphp
<div class="dlv-page">
    <div class="dlv-head">
        <div>
            <div class="dlv-title">Waybill {{ $parcel->waybill_no ?: '—' }}</div>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('delivery.index') }}" class="dlv-btn outline">← List</a>
            <a href="{{ route('delivery.create') }}" class="dlv-btn">+ New</a>
        </div>
    </div>

    <div class="dlv-card">
        <h3>Tracking</h3>
        <div class="dlv-grid">
            <div class="dlv-item">
                <label>Current Status</label>
                <div>
                    <span class="dlv-status {{ $badge }}">{{ $parcel->current_status ?: 'pending' }}</span>
                </div>
            </div>
            <div class="dlv-item">
                <label>Last Update</label>
                <div>{{ optional($parcel->last_update_time)->format('Y-m-d H:i:s') ?: '—' }}</div>
            </div>
            <div class="dlv-item">
                <label>API Status</label>
                <div>
                    {{ $parcel->api_status_code ?: '—' }}
                    @if($parcel->api_status_code)
                        — {{ $fardar->statusMessage((int) $parcel->api_status_code, $parcel->waybill_mode === 'existing') }}
                    @endif
                </div>
            </div>
            <div class="dlv-item">
                <label>Mode</label>
                <div>{{ strtoupper($parcel->waybill_mode) }}</div>
            </div>
        </div>
    </div>

    <div class="dlv-card">
        <h3>Recipient &amp; Parcel</h3>
        <div class="dlv-grid">
            <div class="dlv-item"><label>Order ID</label><div>{{ $parcel->order_id ?: '—' }}</div></div>
            <div class="dlv-item"><label>Sale</label><div>{{ $parcel->transaction_id ? '#'.$parcel->transaction_id.' ('.(optional($parcel->transaction)->invoice_no ?: '—').')' : '—' }}</div></div>
            <div class="dlv-item"><label>Name</label><div>{{ $parcel->recipient_name }}</div></div>
            <div class="dlv-item"><label>Phone</label><div>{{ $parcel->recipient_contact_1 }} @if($parcel->recipient_contact_2) / {{ $parcel->recipient_contact_2 }} @endif</div></div>
            <div class="dlv-item"><label>City</label><div>{{ $parcel->recipient_city }}</div></div>
            <div class="dlv-item"><label>Amount</label><div>{{ number_format((float) $parcel->amount, 2) }}</div></div>
            <div class="dlv-item"><label>Weight</label><div>{{ $parcel->parcel_weight }}</div></div>
            <div class="dlv-item"><label>Exchange</label><div>{{ $parcel->exchange ? 'Yes' : 'No' }}</div></div>
            <div class="dlv-item" style="grid-column:1/-1;"><label>Address</label><div>{{ $parcel->recipient_address }}</div></div>
            <div class="dlv-item" style="grid-column:1/-1;"><label>Description</label><div>{{ $parcel->parcel_description }}</div></div>
        </div>
    </div>

    <div class="dlv-card">
        <h3>Reverse API Endpoint (for Fardar)</h3>
        <p class="dlv-hook">Set this URL in Fardar Reverse API settings so status updates sync automatically:</p>
        <div class="dlv-mono">{{ url('/delivery/webhook/status') }}</div>
    </div>

    @if(! empty($parcel->api_response))
        <div class="dlv-card">
            <h3>API Response</h3>
            <div class="dlv-mono">{{ json_encode($parcel->api_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</div>
        </div>
    @endif
</div>
@endsection
