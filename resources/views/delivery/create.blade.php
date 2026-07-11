@extends('layouts.app')
@section('title', 'New Delivery Pickup')

@section('css')
<style>
.dlv-page { padding: 0 20px 60px; max-width: 920px; }
.dlv-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin: 20px 0 18px; }
.dlv-title { font-size: 20px; font-weight: 800; color: #1e1b4b; }
.dlv-btn { background: #7c5cfc; color: #fff; border: none; border-radius: 8px; padding: 10px 18px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; }
.dlv-btn:hover { background: #6d28d9; color: #fff; text-decoration: none; }
.dlv-btn.outline { background: #fff; color: #7c5cfc; border: 1.5px solid #7c5cfc; }
.dlv-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; margin-bottom: 16px; }
.dlv-card h3 { font-size: 14px; font-weight: 800; color: #374151; margin: 0 0 14px; text-transform: uppercase; letter-spacing: .04em; }
.dlv-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 720px) { .dlv-grid { grid-template-columns: 1fr; } }
.dlv-field label { display: block; font-size: 12px; font-weight: 700; color: #6b7280; margin-bottom: 6px; }
.dlv-field input, .dlv-field select, .dlv-field textarea {
    width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 9px 12px; font-size: 13px; background: #f9fafb;
}
.dlv-field textarea { min-height: 80px; resize: vertical; }
.dlv-field.full { grid-column: 1 / -1; }
.dlv-hint { font-size: 12px; color: #9ca3af; margin-top: 4px; }
.dlv-warn { background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; font-size: 13px; }
.dlv-sale-list { max-height: 180px; overflow: auto; border: 1px solid #e5e7eb; border-radius: 8px; }
.dlv-sale-item { display: block; padding: 10px 12px; border-bottom: 1px solid #f3f4f6; text-decoration: none; color: #374151; font-size: 13px; }
.dlv-sale-item:hover { background: #f5f3ff; text-decoration: none; }
.dlv-sale-item strong { color: #7c5cfc; }
.dlv-mode { display: flex; gap: 10px; margin-bottom: 8px; }
.dlv-mode label { display: flex; align-items: center; gap: 8px; padding: 10px 14px; border: 1.5px solid #e5e7eb; border-radius: 10px; cursor: pointer; font-size: 13px; font-weight: 600; background: #fff; }
.dlv-mode input { margin: 0; }
</style>
@endsection

@section('content')
<div class="dlv-page">
    <div class="dlv-head">
        <div class="dlv-title">New Pickup Request</div>
        <a href="{{ route('delivery.index') }}" class="dlv-btn outline">← Delivery List</a>
    </div>

    @if(! $fardarConfigured)
        <div class="dlv-warn">
            Fardar API credentials are missing. Set <code>FARDAR_CLIENT_ID</code> and <code>FARDAR_API_KEY</code> before submitting.
        </div>
    @endif

    @if(! empty($existingParcel))
        <div class="dlv-warn" style="background:#dcfce7;border-color:#16a34a;color:#166534;">
            This sale already has waybill <strong>{{ $existingParcel->waybill_no }}</strong>
            ({{ $existingParcel->current_status ?: 'pending' }}).
            <a href="{{ route('delivery.show', $existingParcel->id) }}" style="color:#166534;font-weight:700;">View delivery →</a>
        </div>
    @endif

    <div class="dlv-card">
        <h3>Link Sale (optional)</h3>
        <form method="GET" action="{{ route('delivery.create') }}" style="display:flex;gap:8px;margin-bottom:12px;">
            <input type="number" name="transaction_id" value="{{ $defaults['transaction_id'] }}" placeholder="Sale / Transaction ID" style="flex:1;border:1px solid #d1d5db;border-radius:8px;padding:9px 12px;">
            <button type="submit" class="dlv-btn outline">Load Sale</button>
        </form>
        @if($recentSales->count())
            <div class="dlv-sale-list">
                @foreach($recentSales as $sale)
                    <a class="dlv-sale-item" href="{{ route('delivery.create', ['transaction_id' => $sale->id]) }}">
                        <strong>{{ $sale->invoice_no ?: '#'.$sale->id }}</strong>
                        — {{ optional($sale->contact)->name ?: 'Walk-in' }}
                        · {{ number_format((float) $sale->final_total, 2) }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('delivery.store') }}">
        @csrf
        <input type="hidden" name="transaction_id" value="{{ $defaults['transaction_id'] }}">

        <div class="dlv-card">
            <h3>Waybill Mode</h3>
            <div class="dlv-mode">
                <label>
                    <input type="radio" name="waybill_mode" value="new" @checked(($defaults['waybill_mode'] ?? 'new') === 'new') onchange="toggleWaybillId()">
                    New Waybill
                </label>
                <label>
                    <input type="radio" name="waybill_mode" value="existing" @checked(($defaults['waybill_mode'] ?? '') === 'existing') onchange="toggleWaybillId()">
                    Existing Waybill
                </label>
            </div>
            <div class="dlv-field" id="waybill_id_wrap" style="display:none;margin-top:10px;">
                <label>Waybill ID (Tracking Number)</label>
                <input type="text" name="waybill_id" value="{{ old('waybill_id', $defaults['waybill_id'] ?? '') }}" placeholder="e.g. CRE / CCP number">
            </div>
        </div>

        <div class="dlv-card">
            <h3>Parcel Details</h3>
            <div class="dlv-grid">
                <div class="dlv-field">
                    <label>Order ID / Invoice No</label>
                    <input type="text" name="order_id" value="{{ old('order_id', $defaults['order_id']) }}">
                </div>
                <div class="dlv-field">
                    <label>Parcel Weight *</label>
                    <input type="text" name="parcel_weight" value="{{ old('parcel_weight', $defaults['parcel_weight']) }}" required>
                </div>
                <div class="dlv-field full">
                    <label>Parcel Description *</label>
                    <textarea name="parcel_description" required>{{ old('parcel_description', $defaults['parcel_description']) }}</textarea>
                </div>
                <div class="dlv-field">
                    <label>Amount (COD / Value) *</label>
                    <input type="number" step="0.01" name="amount" value="{{ old('amount', $defaults['amount']) }}" required>
                </div>
                <div class="dlv-field">
                    <label>Exchange *</label>
                    <select name="exchange" required>
                        <option value="0" @selected(old('exchange', $defaults['exchange']) == '0')>0 — Normal Parcel</option>
                        <option value="1" @selected(old('exchange', $defaults['exchange']) == '1')>1 — Exchange Parcel</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="dlv-card">
            <h3>Recipient</h3>
            <div class="dlv-grid">
                <div class="dlv-field">
                    <label>Name *</label>
                    <input type="text" name="recipient_name" value="{{ old('recipient_name', $defaults['recipient_name']) }}" required>
                </div>
                <div class="dlv-field">
                    <label>Contact 1 *</label>
                    <input type="text" name="recipient_contact_1" value="{{ old('recipient_contact_1', $defaults['recipient_contact_1']) }}" required>
                </div>
                <div class="dlv-field">
                    <label>Contact 2</label>
                    <input type="text" name="recipient_contact_2" value="{{ old('recipient_contact_2', $defaults['recipient_contact_2']) }}">
                </div>
                <div class="dlv-field">
                    <label>City *</label>
                    <input type="text" name="recipient_city" value="{{ old('recipient_city', $defaults['recipient_city']) }}" required>
                    <div class="dlv-hint">Must match a Fardar city name (e.g. Matara, Colombo)</div>
                </div>
                <div class="dlv-field full">
                    <label>Address *</label>
                    <textarea name="recipient_address" required>{{ old('recipient_address', $defaults['recipient_address']) }}</textarea>
                </div>
            </div>
        </div>

        <button type="submit" class="dlv-btn" {{ $fardarConfigured ? '' : 'disabled' }}>Submit Pickup to Fardar</button>
    </form>
</div>
@endsection

@section('javascript')
<script>
function toggleWaybillId() {
    var mode = document.querySelector('input[name="waybill_mode"]:checked');
    var wrap = document.getElementById('waybill_id_wrap');
    if (!mode || !wrap) return;
    wrap.style.display = mode.value === 'existing' ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', toggleWaybillId);
</script>
@endsection
