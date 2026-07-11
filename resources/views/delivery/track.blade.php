<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Track Order — {{ config('app.name', 'PrintWorks') }}</title>
    <style>
        :root {
            --red: #ED1C24;
            --ink: #111827;
            --muted: #6b7280;
            --bg: #f3f4f6;
            --card: #ffffff;
            --ok: #15803d;
            --warn: #92400e;
            --info: #1d4ed8;
            --bad: #b91c1c;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: linear-gradient(180deg, #fff5f5 0%, var(--bg) 40%, #eef2ff 100%);
            color: var(--ink);
            min-height: 100vh;
        }
        .wrap { max-width: 560px; margin: 0 auto; padding: 28px 16px 48px; }
        .brand {
            text-align: center;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--red);
            margin-bottom: 8px;
        }
        h1 {
            text-align: center;
            font-size: 26px;
            margin: 0 0 6px;
            font-weight: 800;
        }
        .sub {
            text-align: center;
            color: var(--muted);
            font-size: 14px;
            margin: 0 0 22px;
        }
        .card {
            background: var(--card);
            border-radius: 18px;
            padding: 22px 20px;
            box-shadow: 0 10px 30px rgba(17, 24, 39, .08);
            border: 1px solid #e5e7eb;
            margin-bottom: 14px;
        }
        .status-pill {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 999px;
            font-weight: 800;
            font-size: 14px;
        }
        .status-pill.pending { background: #fef3c7; color: var(--warn); }
        .status-pill.transit { background: #dbeafe; color: var(--info); }
        .status-pill.delivered { background: #dcfce7; color: var(--ok); }
        .status-pill.failed { background: #fee2e2; color: var(--bad); }
        .hero-status { text-align: center; margin: 8px 0 18px; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 520px) { .meta { grid-template-columns: 1fr; } }
        .meta label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 4px;
        }
        .meta div { font-size: 15px; font-weight: 700; word-break: break-word; }
        .timeline { list-style: none; margin: 0; padding: 0; }
        .timeline li {
            display: grid;
            grid-template-columns: 18px 1fr;
            gap: 12px;
            position: relative;
            padding-bottom: 16px;
        }
        .timeline li:last-child { padding-bottom: 0; }
        .timeline li::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 16px;
            bottom: 0;
            width: 2px;
            background: #e5e7eb;
        }
        .timeline li:last-child::before { display: none; }
        .dot {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--red);
            border: 3px solid #fecaca;
            margin-top: 2px;
        }
        .t-status { font-weight: 800; font-size: 14px; }
        .t-at { color: var(--muted); font-size: 12px; margin-top: 2px; }
        .foot {
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
@php
    $badge = \App\DeliveryParcel::statusBadgeClass($parcel->current_status);
    $history = collect($parcel->status_history ?? [])->reverse()->values();
    if ($history->isEmpty() && $parcel->current_status) {
        $history = collect([[
            'status' => $parcel->current_status,
            'at' => optional($parcel->last_update_time)->format('Y-m-d H:i:s') ?: optional($parcel->created_at)->format('Y-m-d H:i:s'),
        ]]);
    }
@endphp
<div class="wrap">
    <div class="brand">{{ config('app.name', 'PrintWorks') }}</div>
    <h1>Live Order Tracking</h1>
    <p class="sub">Follow your parcel status in real time</p>

    <div class="card">
        <div class="hero-status">
            <span class="status-pill {{ $badge }}">{{ $parcel->current_status ?: 'Pending' }}</span>
        </div>
        <div class="meta">
            <div>
                <label>Courier</label>
                <div>{{ \App\DeliveryParcel::COURIER_NAME }}</div>
            </div>
            <div>
                <label>Tracking ID</label>
                <div>{{ $parcel->waybill_no ?: '—' }}</div>
            </div>
            <div>
                <label>Order / Invoice</label>
                <div>{{ optional($parcel->transaction)->invoice_no ?: ($parcel->order_id ?: '—') }}</div>
            </div>
            <div>
                <label>Delivery Fee</label>
                <div>LKR {{ number_format((float) (optional($parcel->transaction)->shipping_charges ?? 0), 2) }}</div>
            </div>
            <div>
                <label>COD / Parcel Value</label>
                <div>LKR {{ number_format((float) $parcel->amount, 2) }}</div>
            </div>
            <div>
                <label>Recipient</label>
                <div>{{ $parcel->recipient_name }}</div>
            </div>
            <div>
                <label>City</label>
                <div>{{ $parcel->recipient_city }}</div>
            </div>
            <div>
                <label>Weight</label>
                <div>{{ $parcel->parcel_weight ?: '—' }}</div>
            </div>
            <div>
                <label>Last update</label>
                <div>{{ optional($parcel->last_update_time)->format('Y-m-d H:i') ?: optional($parcel->updated_at)->format('Y-m-d H:i') }}</div>
            </div>
            @if(! empty($parcel->recipient_address))
            <div style="grid-column:1/-1;">
                <label>Address</label>
                <div style="font-weight:600;">{{ $parcel->recipient_address }}</div>
            </div>
            @endif
            @if(! empty($parcel->parcel_description))
            <div style="grid-column:1/-1;">
                <label>Item</label>
                <div style="font-weight:600;">{{ $parcel->parcel_description }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div style="font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;margin-bottom:14px;">Status timeline</div>
        <ul class="timeline">
            @forelse($history as $row)
                <li>
                    <span class="dot"></span>
                    <div>
                        <div class="t-status">{{ $row['status'] ?? '—' }}</div>
                        <div class="t-at">{{ $row['at'] ?? '' }}</div>
                    </div>
                </li>
            @empty
                <li>
                    <span class="dot"></span>
                    <div>
                        <div class="t-status">Waiting for first update</div>
                        <div class="t-at">Status will appear here when the courier updates the shipment.</div>
                    </div>
                </li>
            @endforelse
        </ul>
    </div>

    <div class="foot">Powered by {{ config('app.name', 'PrintWorks') }} · Refresh this page for the latest status</div>
</div>
</body>
</html>
