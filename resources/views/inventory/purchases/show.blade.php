@extends('layouts.app')
@section('title', 'Raw Material Purchase '.$purchase->ref_no)

@section('css')
<style>
.rmp-page { padding: 0 20px 60px; max-width: 960px; margin: 0 auto; }
.rmp-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:18px 20px; margin:18px 0; }
.rmp-btn { border:none; border-radius:9px; padding:9px 16px; font-size:13px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; background:#0ea5e9; color:#fff; }
.rmp-btn-secondary { background:#f3f4f6; color:#374151; }
.rmp-table { width:100%; border-collapse:collapse; font-size:13px; margin-top:12px; }
.rmp-table th, .rmp-table td { padding:10px 8px; border-bottom:1px solid #f3f4f6; text-align:left; }
.rmp-badge { font-size:11px; font-weight:800; padding:3px 10px; border-radius:20px; text-transform:uppercase; }
.rmp-badge.received, .rmp-badge.pay-paid { background:#dcfce7; color:#15803d; }
.rmp-badge.ordered, .rmp-badge.pay-partial { background:#fef3c7; color:#b45309; }
.rmp-badge.pay-due, .rmp-badge.overdue { background:#fee2e2; color:#b91c1c; }
.rmp-pay-box { margin-top:16px; padding:16px; border:1px dashed #93c5fd; border-radius:12px; background:#f8fbff; }
.rmp-pay-box h3 { margin:0 0 12px; font-size:15px; font-weight:800; color:#1e3a8a; }
.rmp-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; align-items:end; }
@media (max-width:800px){ .rmp-grid{ grid-template-columns:1fr 1fr; } }
body #scrollable-container .rmp-page input,
body #scrollable-container .rmp-page select {
    color-scheme: light !important;
    background: #fff !important;
    color: #111827 !important;
    -webkit-text-fill-color: #111827 !important;
}
</style>
@endsection

@section('content')
@php
    $paid = $purchase->amountPaid();
    $due = $purchase->amountDue();
    $dueDate = $purchase->dueDate();
    $overdue = $purchase->isOverdue();
@endphp
<div class="rmp-page">
    @if(session('success'))
        <div class="alert alert-success" style="margin-top:16px;">{{ session('success') }}</div>
    @endif

    <div class="rmp-card">
        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;">
            <div>
                <div style="font-size:12px;color:#9ca3af;font-weight:700;">{{ $purchase->ref_no }}</div>
                <h1 style="margin:4px 0 8px;font-size:22px;font-weight:800;">Raw Material Purchase</h1>
                <div style="font-size:13px;color:#6b7280;line-height:1.7;">
                    Date: {{ $purchase->purchase_date?->format('d M Y') }} ·
                    Supplier: {{ $purchase->supplier?->supplier_business_name ?: ($purchase->supplier?->name ?: '—') }} ·
                    <span class="rmp-badge {{ $purchase->status }}">{{ $purchase->status }}</span>
                    @if($dueDate)
                        <br>Pay term due: <strong>{{ $dueDate->format('d M Y') }}</strong>
                        @if($overdue)<span class="rmp-badge overdue">Overdue</span>@endif
                    @endif
                    @if($advanceBalance > 0.009)
                        <br>Supplier advance balance: <strong>Rs. {{ number_format($advanceBalance, 2) }}</strong>
                    @endif
                </div>
                @if($purchase->notes)
                    <div style="margin-top:10px;font-size:13px;color:#374151;">{{ $purchase->notes }}</div>
                @endif
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="{{ route('inventory.purchases.index') }}" class="rmp-btn rmp-btn-secondary">All purchases</a>
                @if($purchase->contact_id)
                    <a href="{{ route('inventory.purchases.supplier_statement', ['supplier_id' => $purchase->contact_id]) }}" class="rmp-btn rmp-btn-secondary">Supplier statement</a>
                @endif
                @if($due > 0.009)
                    <a href="#rmpAddPayment" class="rmp-btn">Add payment</a>
                @endif
                @if($purchase->status === 'ordered')
                <form method="POST" action="{{ route('inventory.purchases.receive', $purchase) }}">
                    @csrf
                    <button type="submit" class="rmp-btn" onclick="return confirm('Mark received and add stock to Raw Materials?')">Mark Received</button>
                </form>
                @endif
            </div>
        </div>

        <table class="rmp-table">
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Unit</th>
                    <th>Qty</th>
                    <th>Cost / unit</th>
                    <th>Line total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchase->lines as $line)
                <tr>
                    <td><strong>{{ $line->material->name ?? '—' }}</strong></td>
                    <td>{{ $line->material->unit->abbreviation ?? $line->material->unit->name ?? '—' }}</td>
                    <td>{{ rtrim(rtrim(number_format((float) $line->quantity, 4, '.', ''), '0'), '.') }}</td>
                    <td>Rs. {{ number_format((float) $line->unit_cost, 2) }}</td>
                    <td>Rs. {{ number_format((float) $line->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="text-align:right;margin-top:12px;font-size:14px;font-weight:700;line-height:1.8;">
            @if((float) ($purchase->discount_amount ?? 0) > 0 || (float) ($purchase->tax_amount ?? 0) > 0)
                Before tax: Rs. {{ number_format((float) ($purchase->total_before_tax ?? $purchase->total_amount), 2) }}<br>
                @if($purchase->discount_type === 'percentage')
                    Discount: {{ number_format((float) ($purchase->discount_amount ?? 0), 2) }}%
                @else
                    Discount: Rs. {{ number_format((float) ($purchase->discount_amount ?? 0), 2) }}
                @endif
                <br>
                Tax: Rs. {{ number_format((float) ($purchase->tax_amount ?? 0), 2) }}<br>
            @endif
            Total: Rs. {{ number_format((float) $purchase->total_amount, 2) }}<br>
            Paid: Rs. {{ number_format($paid, 2) }}<br>
            Due: Rs. {{ number_format($due, 2) }}
            <span class="rmp-badge pay-{{ $purchase->payment_status ?: 'due' }}">{{ $purchase->payment_status ?: 'due' }}</span>
        </div>

        <h3 style="margin:18px 0 8px;font-size:15px;font-weight:800;">Payments</h3>
        <table class="rmp-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Account</th>
                    <th>Details</th>
                    <th>Amount</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchase->payments as $payment)
                <tr>
                    <td>{{ $payment->paid_on?->format('d M Y') }}</td>
                    <td>{{ $payment->method }}</td>
                    <td>
                        @php
                            $accName = !empty($payment->account_id) && !empty($accounts[$payment->account_id])
                                ? $accounts[$payment->account_id]
                                : '—';
                        @endphp
                        {{ $accName }}
                    </td>
                    <td>
                        @if($payment->cheque_number) Cheque {{ $payment->cheque_number }}@if($payment->cheque_date) · {{ $payment->cheque_date->format('d M Y') }}@endif<br>@endif
                        @if($payment->reference_no) Ref {{ $payment->reference_no }}<br>@endif
                        @if($payment->attachment)
                            <a href="{{ asset('uploads/raw-material-payments/'.$payment->attachment) }}" target="_blank">Open slip</a>
                        @endif
                        @if(!$payment->cheque_number && !$payment->reference_no && !$payment->attachment) — @endif
                    </td>
                    <td>Rs. {{ number_format((float) $payment->amount, 2) }}</td>
                    <td>{{ $payment->note ?: '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6">No payment recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if($due > 0.009)
        <div class="rmp-pay-box" id="rmpAddPayment">
            <h3>Add payment · Remaining due Rs. {{ number_format($due, 2) }}</h3>
            <form method="POST" action="{{ route('inventory.purchases.payments.store', $purchase) }}" enctype="multipart/form-data">
                @csrf
                <div class="rmp-grid">
                    <div>
                        <label style="font-size:11px;font-weight:700;color:#6b7280;">Amount</label>
                        <input type="number" name="amount" min="0.01" max="{{ $due }}" step="0.01" value="{{ number_format($due, 2, '.', '') }}" required style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px;">
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:#6b7280;">Paid on</label>
                        <input type="date" name="paid_on" value="{{ date('Y-m-d') }}" required style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px;">
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:700;color:#6b7280;">Method</label>
                        <select name="method" id="rmpShowMethod" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px;">
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="bank">Bank transfer</option>
                            <option value="card">Card</option>
                            @if($advanceBalance > 0.009)
                                <option value="advance">Advance balance (Rs. {{ number_format($advanceBalance, 2) }})</option>
                            @endif
                        </select>
                    </div>
                    <div id="rmpShowAccount">
                        <label style="font-size:11px;font-weight:700;color:#6b7280;">Payment from account</label>
                        <select name="account_id" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px;">
                            @foreach($accounts ?? [] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="rmpShowCheque" style="display:none;margin-top:10px;">
                    <label style="font-size:11px;font-weight:700;color:#6b7280;">Cheque number</label>
                    <input type="text" name="cheque_number" placeholder="Cheque number" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px;margin-bottom:8px;">
                    <label style="font-size:11px;font-weight:700;color:#6b7280;">Cheque date</label>
                    <input type="date" name="cheque_date" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px;">
                </div>
                <div id="rmpShowBank" style="display:none;margin-top:10px;">
                    <label style="font-size:11px;font-weight:700;color:#6b7280;">Transfer id / reference</label>
                    <input type="text" name="reference_no" placeholder="Bank reference or transfer id" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px;margin-bottom:8px;">
                    <label style="font-size:11px;font-weight:700;color:#6b7280;">Transfer slip (PDF, JPG, or PNG)</label>
                    <input type="file" name="payment_attachment" accept=".pdf,.jpg,.jpeg,.png" style="width:100%;">
                </div>
                <div style="margin-top:10px;">
                    <label style="font-size:11px;font-weight:700;color:#6b7280;">Note</label>
                    <input type="text" name="note" placeholder="Optional" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px;">
                </div>
                <button type="submit" class="rmp-btn" style="margin-top:12px;">Save payment</button>
            </form>
            @error('amount')
                <p style="color:#b91c1c;font-weight:700;margin-top:8px;">{{ $message }}</p>
            @enderror
            @error('cheque_number')
                <p style="color:#b91c1c;font-weight:700;margin-top:8px;">{{ $message }}</p>
            @enderror
            @error('cheque_date')
                <p style="color:#b91c1c;font-weight:700;margin-top:8px;">{{ $message }}</p>
            @enderror
            @error('reference_no')
                <p style="color:#b91c1c;font-weight:700;margin-top:8px;">{{ $message }}</p>
            @enderror
        </div>
        @endif
    </div>
</div>
@endsection

@section('javascript')
<script>
document.getElementById('rmpShowMethod')?.addEventListener('change', function () {
    document.getElementById('rmpShowCheque').style.display = this.value === 'cheque' ? 'block' : 'none';
    document.getElementById('rmpShowBank').style.display = this.value === 'bank' ? 'block' : 'none';
    var acc = document.getElementById('rmpShowAccount');
    if (acc) acc.style.display = this.value === 'advance' ? 'none' : 'block';
});
</script>
@endsection
