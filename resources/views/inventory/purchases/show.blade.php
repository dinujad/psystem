@extends('layouts.app')
@section('title', 'Raw Material Purchase '.$purchase->ref_no)

@section('css')
<style>
.rmp-page { padding: 0 20px 60px; max-width: 900px; margin: 0 auto; }
.rmp-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:18px 20px; margin:18px 0; }
.rmp-btn { border:none; border-radius:9px; padding:9px 16px; font-size:13px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-flex; background:#0ea5e9; color:#fff; }
.rmp-btn-secondary { background:#f3f4f6; color:#374151; }
.rmp-table { width:100%; border-collapse:collapse; font-size:13px; margin-top:12px; }
.rmp-table th, .rmp-table td { padding:10px 8px; border-bottom:1px solid #f3f4f6; text-align:left; }
.rmp-badge { font-size:11px; font-weight:800; padding:3px 10px; border-radius:20px; text-transform:uppercase; }
.rmp-badge.received { background:#dcfce7; color:#15803d; }
.rmp-badge.ordered { background:#fef3c7; color:#b45309; }
</style>
@endsection

@section('content')
<div class="rmp-page">
    @if(session('success'))
        <div class="alert alert-success" style="margin-top:16px;">{{ session('success') }}</div>
    @endif

    <div class="rmp-card">
        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;">
            <div>
                <div style="font-size:12px;color:#9ca3af;font-weight:700;">{{ $purchase->ref_no }}</div>
                <h1 style="margin:4px 0 8px;font-size:22px;font-weight:800;">Raw Material Purchase</h1>
                <div style="font-size:13px;color:#6b7280;">
                    Date: {{ $purchase->purchase_date?->format('d M Y') }} ·
                    Supplier: {{ $purchase->supplier?->supplier_business_name ?: ($purchase->supplier?->name ?: '—') }} ·
                    <span class="rmp-badge {{ $purchase->status }}">{{ $purchase->status }}</span>
                </div>
                @if($purchase->notes)
                    <div style="margin-top:10px;font-size:13px;color:#374151;">{{ $purchase->notes }}</div>
                @endif
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="{{ route('inventory.purchases.index') }}" class="rmp-btn rmp-btn-secondary">All purchases</a>
                <a href="{{ route('inventory.index') }}" class="rmp-btn rmp-btn-secondary">Raw Materials</a>
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
        <div style="text-align:right;margin-top:12px;font-size:16px;font-weight:800;">
            Total: Rs. {{ number_format((float) $purchase->total_amount, 2) }}
        </div>
    </div>
</div>
@endsection
