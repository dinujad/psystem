@extends('layouts.app')
@section('title', 'Raw supplier statement')

@section('css')
<style>
.rmp-page { padding: 0 20px 60px; max-width: 1000px; margin: 0 auto; }
.rmp-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:18px 20px; margin:18px 0; }
.rmp-btn { border:none; border-radius:9px; padding:9px 16px; font-size:13px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-flex; background:#0ea5e9; color:#fff; }
.rmp-btn-secondary { background:#f3f4f6; color:#374151; }
.rmp-table { width:100%; border-collapse:collapse; font-size:13px; margin-top:12px; }
.rmp-table th, .rmp-table td { padding:10px 8px; border-bottom:1px solid #f3f4f6; text-align:left; }
.rmp-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-top:14px; }
.rmp-stat { background:#f8fafc; border:1px solid #e5e7eb; border-radius:10px; padding:12px; }
.rmp-stat span { display:block; font-size:11px; color:#6b7280; font-weight:700; text-transform:uppercase; }
.rmp-stat strong { font-size:16px; }
@media (max-width:800px){ .rmp-stats{ grid-template-columns:1fr 1fr; } }
</style>
@endsection

@section('content')
<div class="rmp-page">
    <div class="rmp-card">
        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;">
            <div>
                <h1 style="margin:0 0 6px;font-size:22px;font-weight:800;">Supplier statement</h1>
                <p style="margin:0;color:#6b7280;font-size:13px;">Raw material purchases, payments, and advance balance.</p>
            </div>
            <a href="{{ route('inventory.purchases.index') }}" class="rmp-btn rmp-btn-secondary">Purchases</a>
        </div>

        <form method="GET" action="{{ route('inventory.purchases.supplier_statement') }}" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;align-items:end;">
            <div style="flex:1;min-width:220px;">
                <label style="font-size:11px;font-weight:700;color:#6b7280;">Supplier</label>
                <select name="supplier_id" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px;">
                    <option value="">Select supplier</option>
                    @foreach($suppliers as $id => $name)
                        <option value="{{ $id }}" @selected((int)$supplierId === (int)$id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rmp-btn">Show statement</button>
        </form>

        @if($supplier)
            <div class="rmp-stats">
                <div class="rmp-stat"><span>Purchases</span><strong>Rs. {{ number_format($purchaseTotal, 2) }}</strong></div>
                <div class="rmp-stat"><span>Payments</span><strong>Rs. {{ number_format($paymentTotal, 2) }}</strong></div>
                <div class="rmp-stat"><span>Purchase due</span><strong>Rs. {{ number_format($purchaseDue, 2) }}</strong></div>
                <div class="rmp-stat"><span>Advance balance</span><strong>Rs. {{ number_format($advanceBalance, 2) }}</strong></div>
            </div>

            <h3 style="margin:18px 0 8px;font-size:15px;font-weight:800;">Purchases</h3>
            <table class="rmp-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Ref</th>
                        <th>Due date</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                    <tr>
                        <td>{{ $purchase->purchase_date?->format('d M Y') }}</td>
                        <td>{{ $purchase->ref_no }}</td>
                        <td>{{ $purchase->dueDate()?->format('d M Y') ?: '—' }}</td>
                        <td>Rs. {{ number_format((float)$purchase->total_amount, 2) }}</td>
                        <td>Rs. {{ number_format($purchase->amountPaid(), 2) }}</td>
                        <td>Rs. {{ number_format($purchase->amountDue(), 2) }}</td>
                        <td>{{ $purchase->payment_status }}</td>
                        <td><a href="{{ route('inventory.purchases.show', $purchase) }}">Open</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="8">No raw purchases for this supplier.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <h3 style="margin:18px 0 8px;font-size:15px;font-weight:800;">Payments</h3>
            <table class="rmp-table">
                <thead>
                    <tr><th>Date</th><th>Purchase</th><th>Method</th><th>Amount</th><th>Note</th></tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment['date'] ? \Carbon\Carbon::parse($payment['date'])->format('d M Y') : '—' }}</td>
                        <td><a href="{{ route('inventory.purchases.show', $payment['purchase_id']) }}">{{ $payment['ref'] }}</a></td>
                        <td>{{ $payment['method'] }}</td>
                        <td>Rs. {{ number_format($payment['amount'], 2) }}</td>
                        <td>{{ $payment['note'] ?: '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5">No payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
