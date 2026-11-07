@extends('layouts.app')
@section('title', 'Purchase cheques')

@section('css')
<style>
.rmp-page { padding: 0 20px 60px; max-width: 1100px; margin: 0 auto; }
.rmp-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:18px 20px; margin:18px 0; }
.rmp-btn { border:none; border-radius:9px; padding:9px 16px; font-size:13px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; background:#0ea5e9; color:#fff; }
.rmp-btn-secondary { background:#f3f4f6; color:#374151; }
.rmp-btn-active { background:#b45309; color:#fff; }
.rmp-btn-success { background:#059669; color:#fff; }
.rmp-table { width:100%; border-collapse:collapse; font-size:13px; margin-top:12px; }
.rmp-table th, .rmp-table td { padding:10px 8px; border-bottom:1px solid #f3f4f6; text-align:left; }
.rmp-table th { font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; }
.rmp-badge { font-size:11px; font-weight:800; padding:3px 10px; border-radius:20px; text-transform:uppercase; display:inline-block; }
.rmp-badge.overdue { background:#fee2e2; color:#b91c1c; }
.rmp-badge.due_soon { background:#fef3c7; color:#b45309; }
.rmp-badge.upcoming { background:#dbeafe; color:#1d4ed8; }
.rmp-filters { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
</style>
@endsection

@section('content')
<div class="rmp-page">
    @include('inventory.purchases._swal_flash')
    <div class="rmp-card">
        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;">
            <div>
                <h1 style="margin:0 0 6px;font-size:22px;font-weight:800;">Pending purchase cheques</h1>
                <p style="margin:0;font-size:13px;color:#6b7280;">
                    Uncleared cheques only. Mark as paid when the cheque is actually paid.
                    <strong style="color:#b91c1c;">{{ (int) $overdueCount }} overdue</strong>
                    ·
                    <strong style="color:#b45309;">{{ (int) $dueSoonCount }} due soon</strong>
                </p>
            </div>
            <a href="{{ route('inventory.purchases.index') }}" class="rmp-btn rmp-btn-secondary">All purchases</a>
        </div>

        <div class="rmp-filters">
            <a href="{{ route('inventory.purchases.due_cheques', ['filter' => 'overdue']) }}"
               class="rmp-btn {{ $filter === 'overdue' ? 'rmp-btn-active' : 'rmp-btn-secondary' }}">Overdue</a>
            <a href="{{ route('inventory.purchases.due_cheques', ['filter' => 'due_soon']) }}"
               class="rmp-btn {{ in_array($filter, ['due_soon', 'due'], true) ? 'rmp-btn-active' : 'rmp-btn-secondary' }}">Due soon (7 days)</a>
            <a href="{{ route('inventory.purchases.due_cheques', ['filter' => 'upcoming']) }}"
               class="rmp-btn {{ $filter === 'upcoming' ? 'rmp-btn-active' : 'rmp-btn-secondary' }}">Later</a>
            <a href="{{ route('inventory.purchases.due_cheques', ['filter' => 'all']) }}"
               class="rmp-btn {{ $filter === 'all' ? 'rmp-btn-active' : 'rmp-btn-secondary' }}">All pending</a>
        </div>

        <table class="rmp-table">
            <thead>
                <tr>
                    <th>Cheque date</th>
                    <th>Status</th>
                    <th>Cheque no.</th>
                    <th>Purchase</th>
                    <th>Supplier</th>
                    <th>Purchase date</th>
                    <th>Amount</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($cheques as $row)
                    @php
                        $chequeDate = $row->cheque_date ? \Carbon\Carbon::parse($row->cheque_date) : null;
                        $status = 'upcoming';
                        $statusLabel = 'Later';
                        if ($chequeDate) {
                            if ($chequeDate->toDateString() < $today) {
                                $status = 'overdue';
                                $statusLabel = 'Overdue';
                            } elseif ($chequeDate->toDateString() <= now()->addDays(7)->toDateString()) {
                                $status = 'due_soon';
                                $statusLabel = 'Due soon';
                            }
                        }
                    @endphp
                    <tr>
                        <td style="font-weight:700;">{{ $chequeDate ? $chequeDate->format('d M Y') : '—' }}</td>
                        <td>
                            <span class="rmp-badge {{ $status }}">{{ $statusLabel }}</span>
                        </td>
                        <td>{{ $row->cheque_number ?: '—' }}</td>
                        <td>{{ $row->ref_no }}</td>
                        <td>{{ $row->supplier_name }}</td>
                        <td>{{ $row->purchase_date ? \Carbon\Carbon::parse($row->purchase_date)->format('d M Y') : '—' }}</td>
                        <td>Rs. {{ number_format((float) $row->amount, 2) }}</td>
                        <td style="display:flex;gap:6px;flex-wrap:wrap;">
                            <a href="{{ route('inventory.purchases.show', $row->purchase_id) }}" class="rmp-btn rmp-btn-secondary" style="padding:6px 10px;">Open</a>
                            @if(!empty($canClear))
                            <form method="POST" action="{{ route('inventory.purchases.payments.mark_cheque_paid', [$row->purchase_id, $row->id]) }}" onsubmit="return confirm('Mark this cheque as paid?');">
                                @csrf
                                <button type="submit" class="rmp-btn rmp-btn-success" style="padding:6px 10px;">Mark paid</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="padding:24px;color:#6b7280;">
                            @if($filter === 'overdue')
                                No overdue cheques.
                            @elseif(in_array($filter, ['due_soon', 'due'], true))
                                No cheques due in the next 7 days.
                            @elseif($filter === 'upcoming')
                                No later pending cheques.
                            @else
                                No pending cheque payments.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
