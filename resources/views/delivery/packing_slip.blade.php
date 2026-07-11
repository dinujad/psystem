<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Packing Slip — {{ $waybill }}</title>
    <style>
        :root {
            --ink: #111;
            --muted: #444;
            --line: #222;
            --w: {{ $width }}{{ $unit }};
            --h: {{ $height }}{{ $unit }};
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #e5e7eb;
            color: var(--ink);
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 20;
            background: #111827;
            color: #fff;
            padding: 12px 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px 14px;
            align-items: end;
            box-shadow: 0 2px 10px rgba(0,0,0,.2);
        }
        .toolbar label {
            display: block;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #9ca3af;
            margin-bottom: 4px;
        }
        .toolbar select,
        .toolbar input {
            height: 34px;
            border-radius: 8px;
            border: 1px solid #374151;
            background: #1f2937;
            color: #fff;
            padding: 0 10px;
            min-width: 90px;
        }
        .toolbar .group { display: flex; flex-direction: column; }
        .toolbar .row { display: flex; gap: 8px; align-items: end; flex-wrap: wrap; }
        .toolbar button,
        .toolbar a.btn {
            height: 34px;
            border: none;
            border-radius: 8px;
            padding: 0 14px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            color: #fff;
            background: #ED1C24;
        }
        .toolbar a.btn.ghost {
            background: transparent;
            border: 1px solid #6b7280;
            color: #e5e7eb;
        }
        .stage {
            padding: 24px;
            display: flex;
            justify-content: center;
        }
        .slip {
            width: var(--w);
            height: var(--h);
            background: #fff;
            border: 1.5px solid #000;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0,0,0,.12);
        }
        .slip.landscape { flex-direction: column; }

        .hdr {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 6px 8px 5px;
            border-bottom: 1.5px solid var(--line);
            min-height: 14%;
            max-height: 18%;
        }
        .hdr-logo {
            flex: 1 1 auto;
            min-width: 0;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            height: 100%;
        }
        .hdr-logo img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: left center;
            display: block;
        }
        .hdr-waybill {
            flex: 0 0 auto;
            text-align: right;
            font-weight: 800;
            font-size: clamp(11px, 2.4vw, 15px);
            line-height: 1.2;
            white-space: nowrap;
            padding-left: 6px;
        }

        .sec {
            padding: 7px 10px 8px;
            border-bottom: 1.5px solid var(--line);
        }
        .sec h3 {
            margin: 0 0 5px;
            font-size: clamp(11px, 2.2vw, 14px);
            font-weight: 800;
        }
        .sec .line {
            font-size: clamp(10px, 2vw, 13px);
            line-height: 1.35;
            margin: 0 0 2px;
            word-break: break-word;
        }
        .sec .line strong { font-weight: 700; }

        .body-cols {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        .slip.landscape .body-cols {
            flex-direction: row;
        }
        .slip.landscape .body-cols .sec {
            flex: 1;
            border-bottom: none;
        }
        .slip.landscape .body-cols .sec + .sec {
            border-left: 1.5px solid var(--line);
        }

        .foot {
            margin-top: auto;
            padding: 8px 10px 10px;
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 8px;
            align-items: end;
            min-height: 28%;
        }
        .slip.landscape .foot {
            grid-template-columns: 1.1fr 1fr 1.2fr;
            min-height: 32%;
        }
        .cod {
            font-size: clamp(13px, 2.8vw, 18px);
            font-weight: 800;
            text-align: right;
            grid-column: 1 / -1;
            margin-bottom: 2px;
        }
        .slip.landscape .cod {
            grid-column: auto;
            text-align: left;
            align-self: start;
        }
        .qr-wrap, .bc-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            gap: 4px;
        }
        .qr-wrap img {
            width: min(28%, 110px);
            min-width: 72px;
            height: auto;
            image-rendering: pixelated;
        }
        .slip.landscape .qr-wrap img {
            width: min(90px, 18vw);
            min-width: 70px;
        }
        .bc-wrap img {
            width: 100%;
            max-width: 220px;
            height: auto;
            max-height: 48px;
        }
        .code-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .02em;
            text-align: center;
        }
        .track-hint {
            font-size: 9px;
            color: var(--muted);
            text-align: center;
            max-width: 140px;
            word-break: break-all;
            line-height: 1.2;
        }

        @media print {
            @page {
                size: {{ $width }}{{ $unit }} {{ $height }}{{ $unit }};
                margin: 0;
            }
            body { background: #fff; }
            .toolbar, .no-print { display: none !important; }
            .stage { padding: 0; }
            .slip {
                box-shadow: none;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
@php
    $recipientAddress = preg_replace("/\r\n|\r|\n/", "\n", (string) $parcel->recipient_address);
    $orderNo = optional($parcel->transaction)->invoice_no ?: ($parcel->order_id ?: '—');
    $cod = number_format((float) $parcel->amount, 2);
    $letterhead = public_path('images/footer.png');
    $letterheadUrl = file_exists($letterhead) ? asset('images/footer.png') : null;
@endphp

<form class="toolbar no-print" method="get" action="{{ route('delivery.packing_slip', $parcel->id) }}">
    <div class="group">
        <label>Orientation</label>
        <select name="orientation" onchange="this.form.submit()">
            <option value="portrait" @selected($orientation === 'portrait')>Vertical (Portrait)</option>
            <option value="landscape" @selected($orientation === 'landscape')>Horizontal (Landscape)</option>
        </select>
    </div>
    <div class="group">
        <label>Size preset</label>
        <select name="size" id="size-preset" onchange="toggleCustom(this.value); this.form.submit()">
            <option value="4x6" @selected($sizeKey === '4x6')>4 × 6 in (label)</option>
            <option value="4x4" @selected($sizeKey === '4x4')>4 × 4 in</option>
            <option value="100x150" @selected($sizeKey === '100x150')>100 × 150 mm</option>
            <option value="a6" @selected($sizeKey === 'a6')>A6</option>
            <option value="a5" @selected($sizeKey === 'a5')>A5</option>
            <option value="a4" @selected($sizeKey === 'a4')>A4</option>
            <option value="custom" @selected($sizeKey === 'custom')>Custom…</option>
        </select>
    </div>
    <div class="row" id="custom-size" style="{{ $sizeKey === 'custom' ? '' : 'display:none' }}">
        <div class="group">
            <label>Width</label>
            <input type="number" step="0.01" min="1" name="width" value="{{ $width }}">
        </div>
        <div class="group">
            <label>Height</label>
            <input type="number" step="0.01" min="1" name="height" value="{{ $height }}">
        </div>
        <div class="group">
            <label>Unit</label>
            <select name="unit">
                <option value="in" @selected($unit === 'in')>in</option>
                <option value="mm" @selected($unit === 'mm')>mm</option>
                <option value="cm" @selected($unit === 'cm')>cm</option>
            </select>
        </div>
        <button type="submit" style="background:#2563eb;">Apply size</button>
    </div>
    <div class="row" style="margin-left:auto;">
        <button type="button" onclick="window.print()">Print packing slip</button>
        <a class="btn ghost" href="{{ route('delivery.show', $parcel->id) }}">← Back</a>
    </div>
</form>

<div class="stage">
    <div class="slip {{ $orientation === 'landscape' ? 'landscape' : 'portrait' }}">
        <div class="hdr">
            <div class="hdr-logo">
                @if($letterheadUrl)
                    <img src="{{ $letterheadUrl }}" alt="Letterhead">
                @else
                    <strong>{{ config('app.name', 'PrintWorks') }}</strong>
                @endif
            </div>
            <div class="hdr-waybill">Waybill ID : {{ $waybill }}</div>
        </div>

        <div class="body-cols">
            <div class="sec">
                <h3>Recipient Details</h3>
                <div class="line"><strong>Name:</strong> {{ $parcel->recipient_name }}</div>
                <div class="line"><strong>Address:</strong> {!! nl2br(e($recipientAddress)) !!}</div>
                <div class="line"><strong>Contact No:</strong> {{ $parcel->recipient_contact_1 }}@if($parcel->recipient_contact_2) / {{ $parcel->recipient_contact_2 }}@endif</div>
                @if($parcel->recipient_city)
                    <div class="line"><strong>City:</strong> {{ $parcel->recipient_city }}</div>
                @endif
            </div>

            <div class="sec">
                <h3>Pickup Details</h3>
                <div class="line"><strong>Name:</strong> {{ $pickupName }}</div>
                @if($pickupAddress !== '')
                    <div class="line"><strong>Address:</strong> {{ $pickupAddress }}</div>
                @endif
                @if($pickupPhone !== '')
                    <div class="line"><strong>Contact No:</strong> {{ $pickupPhone }}</div>
                @endif
                <div class="line"><strong>Weight:</strong> {{ $parcel->parcel_weight }}</div>
                <div class="line"><strong>Order ID:</strong> {{ $orderNo }}</div>
                <div class="line"><strong>Parcel Desc:</strong> {{ $parcel->parcel_description }}</div>
                <div class="line"><strong>Courier:</strong> {{ \App\DeliveryParcel::COURIER_NAME }}</div>
            </div>
        </div>

        <div class="foot">
            <div class="cod">COD : {{ $cod }} LKR</div>

            <div class="qr-wrap">
                <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG($trackUrl, 'QRCODE', 6, 6) }}" alt="Tracking QR">
                <div class="code-label">Scan to track</div>
            </div>

            <div class="bc-wrap">
                <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($waybill, 'C128', 2, 60) }}" alt="Waybill barcode">
                <div class="code-label">{{ $waybill }}</div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCustom(v) {
    var el = document.getElementById('custom-size');
    if (el) el.style.display = (v === 'custom') ? '' : 'none';
}
</script>
</body>
</html>
