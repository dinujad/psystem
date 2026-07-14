@extends('layouts.app')
@section('title', 'Raw Material Purchases')

@section('css')
<style>
.rmp-page { padding: 0 20px 60px; max-width: 1100px; margin: 0 auto; }
.rmp-head { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin:20px 0 16px; }
.rmp-head h1 { margin:0; font-size:20px; font-weight:800; color:#111827; }
.rmp-btn { background:#0ea5e9; color:#fff; border:none; border-radius:9px; padding:9px 16px; font-size:13px; font-weight:700; text-decoration:none; display:inline-flex; }
.rmp-btn:hover { color:#fff; opacity:.92; text-decoration:none; }
.rmp-note { background:#ecfeff; border:1px solid #a5f3fc; color:#155e75; border-radius:10px; padding:10px 14px; font-size:13px; margin-bottom:14px; }
.rmp-table-wrap { background:#fff; border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; }
.rmp-table { width:100%; border-collapse:collapse; font-size:13px; }
.rmp-table th { text-align:left; padding:12px 14px; background:#f9fafb; font-size:11px; color:#6b7280; text-transform:uppercase; border-bottom:1px solid #e5e7eb; }
.rmp-table td { padding:12px 14px; border-bottom:1px solid #f3f4f6; color:#374151; }
.rmp-badge { font-size:11px; font-weight:800; padding:3px 10px; border-radius:20px; text-transform:uppercase; }
.rmp-badge.received { background:#dcfce7; color:#15803d; }
.rmp-badge.ordered { background:#fef3c7; color:#b45309; }
</style>
@endsection

@section('content')
<div class="rmp-page">
    <div class="rmp-head">
        <div>
            <h1>Raw Material Purchases</h1>
            <div style="font-size:13px;color:#6b7280;margin-top:4px;">Purchases that update Raw Materials stock (not Products).</div>
        </div>
        <a href="{{ route('inventory.purchases.create') }}" class="rmp-btn">+ Purchase Raw Materials</a>
    </div>

    <div class="rmp-note">
        Correct flow: <strong>Purchase raw materials</strong> → use in Production → <strong>Convert to Product</strong> → Sell finished goods.
        Product Purchase screen is for buyable sellable products only — not sheets/ink/media.
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="rmp-table-wrap">
        <table class="rmp-table">
            <thead>
                <tr>
                    <th>Ref</th>
                    <th>Date</th>
                    <th>Supplier</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $p)
                <tr>
                    <td><strong>{{ $p->ref_no }}</strong></td>
                    <td>{{ $p->purchase_date?->format('d M Y') }}</td>
                    <td>{{ $p->supplier?->supplier_business_name ?: ($p->supplier?->name ?: '—') }}</td>
                    <td>{{ $p->lines->count() }}</td>
                    <td>Rs. {{ number_format((float) $p->total_amount, 2) }}</td>
                    <td><span class="rmp-badge {{ $p->status }}">{{ $p->status }}</span></td>
                    <td><a href="{{ route('inventory.purchases.show', $p) }}">View</a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:36px;color:#6b7280;">No raw material purchases yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:14px;">{{ $purchases->links() }}</div>
</div>
@endsection
