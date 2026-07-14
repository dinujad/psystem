<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="no-store">
    @if(! empty($parcel) && ($mode ?? '') === 'result')
        <meta http-equiv="refresh" content="60">
    @endif
    <title>Tracking Portal — {{ config('app.name', 'PrintWorks') }}</title>
    <style>
        :root {
            --red: #ED1C24;
            --ink: #111827;
            --muted: #6b7280;
            --card: #ffffff;
            --ok: #15803d;
            --warn: #92400e;
            --info: #1d4ed8;
            --bad: #b91c1c;
            --line: #e5e7eb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(ellipse 80% 50% at 50% -10%, #fecaca 0%, transparent 55%),
                linear-gradient(180deg, #fff7f7 0%, #f3f4f6 45%, #eef2ff 100%);
        }
        .shell { max-width: 640px; margin: 0 auto; padding: 20px 16px 48px; }
        .portal-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }
        .portal-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #111827;
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: 8px 12px;
            border-radius: 999px;
        }
        .portal-badge span { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,.25); }
        .letterhead {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px 16px;
            margin-bottom: 16px;
            box-shadow: 0 8px 24px rgba(17,24,39,.06);
            text-align: center;
        }
        .letterhead img {
            max-width: 100%;
            max-height: 72px;
            width: auto;
            height: auto;
            object-fit: contain;
        }
        .hero {
            text-align: center;
            margin: 8px 0 20px;
        }
        .hero h1 {
            margin: 0 0 6px;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .hero p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.45;
        }
        .card {
            background: var(--card);
            border-radius: 18px;
            padding: 22px 20px;
            border: 1px solid var(--line);
            box-shadow: 0 10px 30px rgba(17,24,39,.07);
            margin-bottom: 14px;
        }
        .search-label {
            display: block;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #6b7280;
            margin-bottom: 8px;
        }
        .search-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .search-row input {
            flex: 1;
            min-width: 180px;
            height: 46px;
            border: 1.5px solid #d1d5db;
            border-radius: 12px;
            padding: 0 14px;
            font-size: 15px;
            font-weight: 600;
        }
        .search-row button {
            height: 46px;
            border: none;
            border-radius: 12px;
            padding: 0 18px;
            background: var(--red);
            color: #fff;
            font-weight: 800;
            font-size: 14px;
            cursor: pointer;
        }
        .err {
            margin-top: 12px;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 13px;
            font-weight: 600;
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
        .hero-status { text-align: center; margin: 4px 0 18px; }
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
        .section-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #6b7280;
            margin: 0 0 14px;
        }
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
            margin-top: 18px;
            line-height: 1.5;
        }
        .foot a { color: var(--red); font-weight: 700; text-decoration: none; }
        .hint {
            margin-top: 14px;
            font-size: 13px;
            color: var(--muted);
            line-height: 1.5;
        }
    </style>
</head>
<body>
@php
    $mode = $mode ?? 'home';
    $search = $search ?? '';
    $error = $error ?? null;
    $letterheadCandidates = [
        public_path('images/footer.png'),
        public_path('images/printworks_logo.png'),
        public_path('images/logo.png'),
    ];
    $letterheadUrl = null;
    foreach ($letterheadCandidates as $letterhead) {
        if (file_exists($letterhead)) {
            $mime = mime_content_type($letterhead) ?: 'image/png';
            $letterheadUrl = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($letterhead));
            break;
        }
    }
    $badge = ! empty($parcel) ? \App\DeliveryParcel::statusBadgeClass($parcel->current_status) : 'pending';
    $history = collect(optional($parcel)->status_history ?? [])->reverse()->values();
    if (! empty($parcel) && $history->isEmpty() && $parcel->current_status) {
        $history = collect([[
            'status' => $parcel->current_status,
            'at' => optional($parcel->last_update_time)->format('Y-m-d H:i:s') ?: optional($parcel->created_at)->format('Y-m-d H:i:s'),
        ]]);
    }
@endphp
<div class="shell">
    <div class="portal-bar">
        <div class="portal-badge"><span></span> Tracking Portal</div>
        <a href="{{ route('delivery.tracking_portal') }}" style="font-size:13px;font-weight:700;color:#6b7280;text-decoration:none;">New search</a>
    </div>

    <div class="letterhead">
        @if($letterheadUrl)
            <img src="{{ $letterheadUrl }}" alt="{{ config('app.name', 'PrintWorks') }}">
        @else
            <strong style="font-size:18px;">{{ config('app.name', 'PrintWorks') }}</strong>
        @endif
    </div>

    <div class="hero">
        <h1>Order Tracking Portal</h1>
        <p>Live courier status for your PrintWorks / Attract shipment — no login required.</p>
    </div>

    <div class="card">
        <form method="get" action="{{ route('delivery.tracking_portal') }}">
            <label class="search-label" for="waybill">Tracking ID / Waybill / Order No</label>
            <div class="search-row">
                <input id="waybill" type="text" name="waybill" value="{{ $search }}" placeholder="e.g. API4305282 or invoice no" required autocomplete="off">
                <button type="submit">Track order</button>
            </div>
        </form>
        @if($error)
            <div class="err">{{ $error }}</div>
        @endif
        @if($mode === 'home' && ! $error)
            <p class="hint">Paste the Tracking ID from your WhatsApp message, or open the Tracking Portal link we sent you for instant live status.</p>
        @endif
    </div>

    @if(! empty($parcel) && $mode === 'result')
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
            <div class="section-title">Live status timeline</div>
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
                            <div class="t-status">Waiting for first courier update</div>
                            <div class="t-at">Status appears here as soon as Fardar updates the shipment.</div>
                        </div>
                    </li>
                @endforelse
            </ul>
        </div>
    @endif

    <div class="foot">
        PrintWorks Tracking Portal · Auto-refreshes every 60s when viewing a shipment<br>
        <a href="{{ route('delivery.tracking_portal') }}">{{ url('/tracking-portal') }}</a>
    </div>
</div>
</body>
</html>
