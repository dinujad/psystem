@extends('layouts.app')
@section('title', 'Delivery')

@section('css')
<style>
.dlv-page { padding: 0 20px 60px; }
.dlv-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin: 20px 0 18px; }
.dlv-title { font-size: 20px; font-weight: 800; color: #1e1b4b; }
.dlv-sub { font-size: 13px; color: #6b7280; margin-top: 4px; }
.dlv-btn { background: #7c5cfc; color: #fff; border: none; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
.dlv-btn:hover { background: #6d28d9; color: #fff; text-decoration: none; }
.dlv-btn.outline { background: #fff; color: #7c5cfc; border: 1.5px solid #7c5cfc; }
.dlv-warn { background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; font-size: 13px; }
.dlv-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.dlv-tab { display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px; border-radius: 22px; font-size: 13px; font-weight: 700; text-decoration: none; background: #fff; border: 1px solid #e5e7eb; color: #374151; }
.dlv-tab:hover { text-decoration: none; border-color: #7c5cfc; color: #7c5cfc; }
.dlv-tab .badge { font-size: 11px; font-weight: 800; padding: 2px 9px; border-radius: 20px; background: #f3f4f6; color: #6b7280; }
.dlv-tab.active { color: #fff; border-color: transparent; }
.dlv-tab.active .badge { background: rgba(255,255,255,.25); color: #fff; }
.dlv-tab.active.all { background: #4f46e5; }
.dlv-tab.active.pending { background: #d97706; }
.dlv-tab.active.transit { background: #2563eb; }
.dlv-tab.active.delivered { background: #16a34a; }
.dlv-tab.active.failed { background: #dc2626; }
.dlv-bar { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px 16px; display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; align-items: center; }
.dlv-bar input { border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 13px; background: #f9fafb; flex: 1; min-width: 200px; }
.dlv-table-wrap { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
.dlv-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.dlv-table th { text-align: left; padding: 12px 14px; background: #f9fafb; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; border-bottom: 1px solid #e5e7eb; }
.dlv-table td { padding: 12px 14px; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: middle; }
.dlv-table tr:hover td { background: #fafafa; }
.dlv-wb { font-weight: 800; color: #7c5cfc; text-decoration: none; }
.dlv-wb:hover { text-decoration: underline; }
.dlv-status { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.dlv-status.pending { background: #fef3c7; color: #92400e; }
.dlv-status.transit { background: #dbeafe; color: #1d4ed8; }
.dlv-status.delivered { background: #dcfce7; color: #15803d; }
.dlv-status.failed { background: #fee2e2; color: #b91c1c; }
.dlv-view { font-size: 12px; font-weight: 700; color: #7c5cfc; text-decoration: none; background: #ede9fe; padding: 5px 12px; border-radius: 8px; }
.dlv-view:hover { background: #ddd6fe; text-decoration: none; }
.dlv-empty { text-align: center; padding: 40px; color: #9ca3af; }
.dlv-muted { color: #9ca3af; font-size: 12px; }
</style>
@endsection

@section('content')
<div class="dlv-page">
    <div class="dlv-head">
        <div>
            <div class="dlv-title">Delivery</div>
            <div class="dlv-sub">Fardar Express Domestic — waybills &amp; tracking</div>
        </div>
        <a href="{{ route('delivery.create') }}" class="dlv-btn">+ New Pickup Request</a>
    </div>

    @if(! $fardarConfigured)
        <div class="dlv-warn">
            Fardar API credentials are missing. Set <code>FARDAR_CLIENT_ID</code> and <code>FARDAR_API_KEY</code> in environment variables.
        </div>
    @endif

    <div class="dlv-tabs">
        @foreach(['all' => 'All', 'pending' => 'Pending', 'transit' => 'In Transit', 'delivered' => 'Delivered', 'failed' => 'Failed'] as $key => $label)
            <a href="{{ route('delivery.index', ['status' => $key, 'q' => $search]) }}"
               class="dlv-tab {{ $key }} {{ $status === $key ? 'active' : '' }}">
                {{ $label }} <span class="badge">{{ $statusCounts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" class="dlv-bar">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search waybill, order, name, phone, city…">
        <button type="submit" class="dlv-btn">Search</button>
    </form>

    <div class="dlv-table-wrap">
        <table class="dlv-table">
            <thead>
                <tr>
                    <th>Waybill</th>
                    <th>Order</th>
                    <th>Recipient</th>
                    <th>City</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($parcels as $parcel)
                    @php $badge = \App\DeliveryParcel::statusBadgeClass($parcel->current_status); @endphp
                    <tr>
                        <td>
                            <a class="dlv-wb" href="{{ route('delivery.show', $parcel->id) }}">
                                {{ $parcel->waybill_no ?: '—' }}
                            </a>
                            <div class="dlv-muted">{{ strtoupper($parcel->waybill_mode) }}</div>
                        </td>
                        <td>
                            {{ $parcel->order_id ?: '—' }}
                            @if($parcel->transaction_id)
                                <div class="dlv-muted">Sale #{{ $parcel->transaction_id }}</div>
                            @endif
                        </td>
                        <td>
                            <div>{{ $parcel->recipient_name }}</div>
                            <div class="dlv-muted">{{ $parcel->recipient_contact_1 }}</div>
                        </td>
                        <td>{{ $parcel->recipient_city }}</td>
                        <td>{{ number_format((float) $parcel->amount, 2) }}</td>
                        <td>
                            <span class="dlv-status {{ $badge }}">
                                {{ $parcel->current_status ?: 'pending' }}
                            </span>
                        </td>
                        <td class="dlv-muted">
                            {{ optional($parcel->last_update_time)->format('Y-m-d H:i') ?: optional($parcel->created_at)->format('Y-m-d H:i') }}
                        </td>
                        <td>
                            <a class="dlv-view" href="{{ route('delivery.show', $parcel->id) }}">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="dlv-empty">No delivery parcels yet. Create a pickup request to get started.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;">
        {{ $parcels->links() }}
    </div>
</div>
@endsection
