@extends('layouts.app')
@section('title', 'Raw purchase due')

@section('css')
<style>
.rmp-page { padding: 0 20px 60px; max-width: 1100px; margin: 0 auto; }
.rmp-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:18px 20px; margin:18px 0; }
.rmp-btn { border:none; border-radius:9px; padding:8px 14px; font-size:13px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-flex; background:#f3f4f6; color:#374151; }
.rmp-btn-active { background:#b45309; color:#fff; }
.rmp-table { width:100%; border-collapse:collapse; font-size:13px; margin-top:12px; }
.rmp-table th, .rmp-table td { padding:10px 8px; border-bottom:1px solid #f3f4f6; text-align:left; }
.rmp-badge { font-size:11px; font-weight:800; padding:3px 10px; border-radius:20px; text-transform:uppercase; }
.rmp-badge.overdue { background:#fee2e2; color:#b91c1c; }
.rmp-badge.soon { background:#fef3c7; color:#b45309; }
</style>
@endsection

@section('content')
<div class="rmp-page">
    @include('inventory.purchases._swal_flash')
    <div class="rmp-card">
        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div>
                <h1 style="margin:0 0 6px;font-size:22px;font-weight:800;">Raw material purchase due</h1>
                <p style="margin:0;color:#6b7280;font-size:13px;">Based on pay term. Reminder window: overdue + next 7 days.</p>
            </div>
            <a href="{{ route('inventory.purchases.index') }}" class="rmp-btn">All purchases</a>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
            <a href="{{ route('inventory.purchases.due', ['filter' => 'due']) }}" class="rmp-btn {{ $filter === 'due' ? 'rmp-btn-active' : '' }}">Due / soon</a>
            <a href="{{ route('inventory.purchases.due', ['filter' => 'overdue']) }}" class="rmp-btn {{ $filter === 'overdue' ? 'rmp-btn-active' : '' }}">Overdue</a>
            <a href="{{ route('inventory.purchases.due', ['filter' => 'upcoming']) }}" class="rmp-btn {{ $filter === 'upcoming' ? 'rmp-btn-active' : '' }}">Next 7 days</a>
            <a href="{{ route('inventory.purchases.due', ['filter' => 'all']) }}" class="rmp-btn {{ $filter === 'all' ? 'rmp-btn-active' : '' }}">All open with pay term</a>
        </div>

        @if($bySupplier->count())
        <h3 style="margin:18px 0 8px;font-size:14px;font-weight:800;">By supplier</h3>
        <table class="rmp-table">
            <thead><tr><th>Supplier</th><th>Open bills</th><th>Overdue</th><th>Amount due</th><th></th></tr></thead>
            <tbody>
            @foreach($bySupplier as $name => $row)
                <tr>
                    <td>{{ $name }}</td>
                    <td>{{ $row['count'] }}</td>
                    <td>{{ $row['overdue'] }}</td>
                    <td>Rs. {{ number_format($row['due'], 2) }}</td>
                    <td>
                        @if(!empty($row['contact_id']))
                            <a href="{{ route('inventory.purchases.supplier_statement', ['supplier_id' => $row['contact_id']]) }}" class="rmp-btn">Statement</a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif

        <table class="rmp-table">
            <thead>
                <tr>
                    <th>Due date</th>
                    <th>Status</th>
                    <th>Purchase</th>
                    <th>Supplier</th>
                    <th>Purchase date</th>
                    <th>Amount due</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                <tr>
                    <td style="font-weight:700;">{{ $purchase->computed_due_date?->format('d M Y') ?: '—' }}</td>
                    <td>
                        @if($purchase->computed_overdue)
                            <span class="rmp-badge overdue">Overdue</span>
                        @elseif($purchase->days_to_due !== null && $purchase->days_to_due <= 7)
                            <span class="rmp-badge soon">In {{ (int) $purchase->days_to_due }} day(s)</span>
                        @else
                            Open
                        @endif
                    </td>
                    <td>{{ $purchase->ref_no }}</td>
                    <td>{{ $purchase->supplier?->supplier_business_name ?: ($purchase->supplier?->name ?: '—') }}</td>
                    <td>{{ $purchase->purchase_date?->format('d M Y') }}</td>
                    <td>Rs. {{ number_format($purchase->remaining_due, 2) }}</td>
                    <td><a href="{{ route('inventory.purchases.show', $purchase) }}" class="rmp-btn">Open / pay</a></td>
                </tr>
                @empty
                <tr><td colspan="7" style="padding:24px;color:#6b7280;">No matching due purchases. Set a pay term when creating credit purchases.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
