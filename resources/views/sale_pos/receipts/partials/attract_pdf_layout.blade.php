@php
    $letterheadPath = public_path('images/attract_letterhead_HQ.png');
    $signPath       = public_path('images/sign.png');
    $logoPath       = public_path('images/logo.jpeg');
    $footerPath     = public_path('images/footer.png');
    if (! file_exists($footerPath)) {
        $footerPath = public_path('images/footer (1).png');
    }

    $letterheadB64 = file_exists($letterheadPath) ? base64_encode(file_get_contents($letterheadPath)) : null;
    $signB64       = file_exists($signPath) ? base64_encode(file_get_contents($signPath)) : null;
    $logoB64       = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
    $footerB64     = file_exists($footerPath) ? base64_encode(file_get_contents($footerPath)) : null;

    // Browser print embeds footer in body; mPDF uses SetHTMLFooter (page bottom).
    $embedFooter = $embed_footer ?? true;

    $docTitle = $document_title ?? 'INVOICE';
    $isQuotation = ! empty($receipt_details->is_quotation);
    if ($isQuotation && empty($document_title)) {
        $docTitle = 'QUOTATION';
    }

    $invNo = (string) ($receipt_details->invoice_no ?? '');
    $label = $isQuotation ? 'QUOTE NO' : 'INVOICE NO';

    $customerAddress = trim(strip_tags($receipt_details->customer_info_address ?? ''));
    if ($customerAddress === '' && ! empty($receipt_details->customer_mobile)) {
        $customerAddress = 'Mobile: '.$receipt_details->customer_mobile;
    }
    if ($customerAddress === '' && ! empty($receipt_details->customer_info)) {
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($receipt_details->customer_info)));
        $cname = trim($receipt_details->customer_name ?? '');
        if ($cname !== '' && str_starts_with($plain, $cname)) {
            $plain = trim(substr($plain, strlen($cname)));
        }
        $customerAddress = $plain !== '' ? $plain : '—';
    }

    $bankTransferTotal = $receipt_details->bank_transfer_total ?? null;
    $brandRed = '#ED1C24';
    $brandPink = '#FCE8E9';
    $brandPinkSoft = '#FFF5F5';

    $bankText = trim((string) ($receipt_details->pdf_bank_details ?? ''));
    if ($bankText === '' && ! empty($receipt_details->preferred_payment_method)) {
        $bankText = 'Payment mode: '.$receipt_details->preferred_payment_method;
    }
    if ($bankText === '' && ! empty($receipt_details->preferred_account_details)) {
        $acc = (array) $receipt_details->preferred_account_details;
        $bankLines = [];
        if (! empty($acc['account_holder_name'])) {
            $bankLines[] = 'Account Name: '.$acc['account_holder_name'];
        }
        if (! empty($acc['account_number'])) {
            $bankLines[] = 'Account Number: '.$acc['account_number'];
        }
        if (! empty($acc['bank_name'])) {
            $bankLines[] = 'Bank: '.$acc['bank_name'];
        }
        if (! empty($acc['branch'])) {
            $bankLines[] = 'Branch: '.$acc['branch'];
        }
        $bankText = implode("\n", $bankLines);
    }
    if ($bankText === '' && ! empty($receipt_details->payments)) {
        $bankText = 'Payment mode: '.collect($receipt_details->payments)->pluck('method')->filter()->unique()->implode(' / ');
    }

    // Split free-text bank box into label/value rows when possible
    $bankRows = [];
    $paymentModeLine = null;
    foreach (preg_split("/\r\n|\n|\r/", $bankText) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (preg_match('/^payment\s*mode\s*:\s*(.+)$/i', $line, $m)) {
            $paymentModeLine = trim($m[1]);
            continue;
        }
        if (preg_match('/^([^:]+):\s*(.+)$/', $line, $m)) {
            $bankRows[] = ['label' => trim($m[1]), 'value' => trim($m[2])];
        } else {
            $bankRows[] = ['label' => '', 'value' => $line];
        }
    }

    $paymentHistory = collect($receipt_details->payments ?? [])->filter(function ($p) {
        return ! empty($p['amount']);
    })->values();
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $docTitle }} {{ $invNo }}</title>
<style>
  @page { margin: 0; size: A4; }
  html, body {
    background: #fff;
    margin: 0;
    padding: 0;
    font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
    color: #111;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    color-adjust: exact !important;
  }
  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    color-adjust: exact !important;
  }
  .a4-sheet {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto;
    background: #fff;
    position: relative;
  }
  .letterhead img { width: 100%; display: block; }
  .content-area { padding: 7mm 12mm 8mm 12mm; }

  .doc-title {
    text-align: center;
    font-size: 26px;
    font-weight: 800;
    color: #ED1C24;
    letter-spacing: 3px;
    margin: 4px 0 14px 0;
    text-transform: uppercase;
  }

  /* Rounded table shells — works in browser print + mPDF */
  .table-shell {
    border: 1.5px solid #ED1C24;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 14px;
    background-color: #FFF5F5;
  }
  .table-shell table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    margin: 0;
    font-size: 12px;
  }
  .table-shell th,
  .table-shell td {
    border: none;
    border-right: 1px solid #ED1C24;
    border-bottom: 1px solid #ED1C24;
    padding: 8px 10px;
    vertical-align: middle;
  }
  .table-shell th:last-child,
  .table-shell td:last-child { border-right: none; }
  .table-shell tr:last-child td { border-bottom: none; }
  .table-shell thead th,
  .table-shell th.lbl-cell {
    background-color: #ED1C24 !important;
    color: #ffffff !important;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 10px;
    letter-spacing: .04em;
    text-align: center;
  }
  .table-shell td {
    background-color: #FFF5F5 !important;
    color: #111111;
    text-align: center;
  }
  .table-shell td.desc,
  .table-shell td.left { text-align: left; }
  .table-shell td.money,
  .table-shell td.amt { text-align: right; white-space: nowrap; font-weight: 700; }

  /* Info grid: label cells red, value cells pink */
  .info-shell .lbl-cell {
    width: 14%;
    text-align: left !important;
    background-color: #ED1C24 !important;
    color: #ffffff !important;
  }
  .info-shell td.val-cell {
    text-align: left;
    background-color: #FCE8E9 !important;
  }

  .prod-shell td.desc { text-align: left; }

  .summary-row {
    width: 100%;
    border-collapse: collapse;
    margin-top: 6px;
    table-layout: fixed;
  }
  .summary-row > td { vertical-align: top; padding: 0; border: none; }
  .summary-row td.pay-col { width: 52%; padding-right: 12px; }
  .summary-row td.total-col { width: 48%; padding-left: 4px; }

  .bank-box {
    background-color: #FCE8E9 !important;
    padding: 12px 14px;
    border: 1.5px solid #ED1C24;
    border-radius: 10px;
    overflow: hidden;
  }
  .bank-box .bank-title {
    color: #ED1C24;
    font-size: 13px;
    font-weight: 800;
    margin: 0 0 8px 0;
  }
  .bank-box .bank-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
  }
  .bank-box .bank-table td {
    padding: 3px 0;
    vertical-align: top;
    border: none;
  }
  .bank-box .bank-table td.lbl {
    font-weight: 700;
    color: #111111;
    width: 38%;
    white-space: nowrap;
  }
  .bank-box .bank-table td.val { color: #333333; }
  .bank-box .mode-line {
    margin-bottom: 6px;
    font-size: 11px;
  }
  .bank-box .mode-line strong { color: #111111; }

  .totals-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
  }
  .totals-table td {
    padding: 4px 2px;
    border: none;
    text-decoration: none;
  }
  .totals-table td.label { text-align: left; color: #333333; }
  .totals-table td.value { text-align: right; white-space: nowrap; color: #111111; }
  .totals-table tr.rule td {
    padding: 0;
    height: 0;
    line-height: 0;
    font-size: 0;
    border-top: 1.5px solid #111111;
  }
  .totals-table tr.rule-double td {
    padding: 0;
    height: 0;
    line-height: 0;
    font-size: 0;
    border-top: 3px double #111111;
  }
  .totals-table tr.grand td {
    font-weight: 800;
    font-size: 13px;
    color: #111111;
    padding-top: 6px;
    padding-bottom: 6px;
  }
  .totals-table tr.paid td {
    color: #16a34a;
    font-weight: 700;
  }
  .totals-table tr.due td {
    color: #ED1C24;
    font-weight: 800;
    font-size: 14px;
    padding-top: 6px;
    padding-bottom: 6px;
  }

  .pay-history-title {
    margin-top: 14px;
    margin-bottom: 6px;
    font-size: 11px;
    font-weight: 800;
    color: #ED1C24;
    text-transform: uppercase;
    letter-spacing: 0.3px;
  }

  /* Push signature + footer to bottom of A4 for browser print */
  .a4-sheet-flex {
    display: flex;
    flex-direction: column;
    min-height: 297mm;
  }
  .a4-sheet-flex .content-area {
    flex: 1 1 auto;
    padding-bottom: 4mm;
  }
  .a4-sheet-flex .sign-zone {
    flex: 0 0 auto;
    margin-top: auto;
    padding: 0 12mm 3mm 12mm;
  }
  .a4-sheet-flex .page-footer {
    flex: 0 0 auto;
    margin-top: 0;
    padding: 0;
  }
  .page-footer img {
    width: 100%;
    display: block;
  }

  .bottom-row {
    width: 100%;
    margin: 0;
    border-collapse: collapse;
  }
  .bottom-row td { vertical-align: bottom; }
  .sys-msg {
    font-size: 10px;
    font-style: italic;
    color: #9ca3af;
    padding-bottom: 6px;
  }
  .sign-block {
    text-align: right;
    font-size: 12px;
    color: #111;
    line-height: 1.35;
    padding-bottom: 2px;
  }
  .sign-block .sign-img {
    height: 58px;
    width: auto;
    display: block;
    margin: 0 0 4px auto;
  }
  .sign-block .sign-closing {
    font-size: 12px;
    color: #222;
    margin: 0 0 2px 0;
  }
  .sign-block .sign-name {
    font-size: 13px;
    font-weight: 700;
    color: #111;
    margin: 0;
  }
  .sign-block .sign-role {
    font-size: 11px;
    color: #444;
    margin: 1px 0 0 0;
  }

  @media print {
    html, body, * {
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
      color-adjust: exact !important;
    }
    .a4-sheet, .a4-sheet-flex { width: 100%; min-height: 297mm; }
    .table-shell,
    .bank-box {
      border-radius: 10px !important;
      overflow: hidden !important;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    .table-shell th,
    .table-shell td,
    .table-shell .lbl-cell,
    .info-shell td.val-cell {
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
  }
</style>
</head>
<body>
<div class="a4-sheet {{ $embedFooter ? 'a4-sheet-flex' : '' }}">

  <div class="letterhead">
    @if(! empty($letterheadB64))
      <img src="data:image/png;base64,{{ $letterheadB64 }}" alt="Letterhead">
    @elseif(! empty($logoB64))
      <div style="padding:14px 16px;text-align:center;">
        <img src="data:image/jpeg;base64,{{ $logoB64 }}" style="max-height:70px;width:auto;" alt="Logo">
      </div>
    @endif
  </div>

  <div class="content-area">
    <div class="doc-title" style="color:#ED1C24;text-align:center;font-size:26px;font-weight:800;letter-spacing:3px;margin:4px 0 14px 0;text-transform:uppercase;">{{ $docTitle }}</div>

    <div class="table-shell info-shell" style="border:1.5px solid #ED1C24;border-radius:10px;overflow:hidden;margin-bottom:14px;background-color:#FCE8E9;">
      <table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0;font-size:12px;">
        <tr>
          <th class="lbl-cell" style="width:14%;background-color:#ED1C24;color:#ffffff;border-right:1px solid #ED1C24;border-bottom:1px solid #ED1C24;padding:8px 10px;text-align:left;font-weight:700;text-transform:uppercase;font-size:10px;" bgcolor="#ED1C24">Name</th>
          <td class="val-cell" style="background-color:#FCE8E9;color:#111111;border-right:1px solid #ED1C24;border-bottom:1px solid #ED1C24;padding:8px 10px;text-align:left;" bgcolor="#FCE8E9">{{ $receipt_details->customer_name ?? 'Walk-In Customer' }}</td>
          <th class="lbl-cell" style="width:14%;background-color:#ED1C24;color:#ffffff;border-right:1px solid #ED1C24;border-bottom:1px solid #ED1C24;padding:8px 10px;text-align:left;font-weight:700;text-transform:uppercase;font-size:10px;" bgcolor="#ED1C24">Date</th>
          <td class="val-cell" style="background-color:#FCE8E9;color:#111111;border-bottom:1px solid #ED1C24;padding:8px 10px;text-align:left;" bgcolor="#FCE8E9">{{ $receipt_details->invoice_date ?? '' }}</td>
        </tr>
        <tr>
          <th class="lbl-cell" style="width:14%;background-color:#ED1C24;color:#ffffff;border-right:1px solid #ED1C24;padding:8px 10px;text-align:left;font-weight:700;text-transform:uppercase;font-size:10px;" bgcolor="#ED1C24">Address</th>
          <td class="val-cell" style="background-color:#FCE8E9;color:#111111;border-right:1px solid #ED1C24;padding:8px 10px;text-align:left;" bgcolor="#FCE8E9">{{ $customerAddress }}</td>
          <th class="lbl-cell" style="width:14%;background-color:#ED1C24;color:#ffffff;border-right:1px solid #ED1C24;padding:8px 10px;text-align:left;font-weight:700;text-transform:uppercase;font-size:10px;" bgcolor="#ED1C24">{{ $label }}</th>
          <td class="val-cell" style="background-color:#FCE8E9;color:#111111;padding:8px 10px;text-align:left;" bgcolor="#FCE8E9">{{ $invNo }}</td>
        </tr>
      </table>
    </div>

    <div class="table-shell prod-shell" style="border:1.5px solid #ED1C24;border-radius:10px;overflow:hidden;margin-bottom:14px;background-color:#FFF5F5;">
      <table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0;font-size:12px;">
        <thead>
          <tr>
            <th style="width:9%;background-color:#ED1C24;color:#ffffff;border-right:1px solid #ED1C24;border-bottom:1px solid #ED1C24;padding:9px 8px;text-align:center;font-weight:700;text-transform:uppercase;font-size:10px;" bgcolor="#ED1C24">Item</th>
            <th style="width:47%;background-color:#ED1C24;color:#ffffff;border-right:1px solid #ED1C24;border-bottom:1px solid #ED1C24;padding:9px 8px;text-align:center;font-weight:700;text-transform:uppercase;font-size:10px;" bgcolor="#ED1C24">Description</th>
            <th style="width:10%;background-color:#ED1C24;color:#ffffff;border-right:1px solid #ED1C24;border-bottom:1px solid #ED1C24;padding:9px 8px;text-align:center;font-weight:700;text-transform:uppercase;font-size:10px;" bgcolor="#ED1C24">Qty</th>
            <th style="width:17%;background-color:#ED1C24;color:#ffffff;border-right:1px solid #ED1C24;border-bottom:1px solid #ED1C24;padding:9px 8px;text-align:center;font-weight:700;text-transform:uppercase;font-size:10px;" bgcolor="#ED1C24">Unit Price</th>
            <th style="width:17%;background-color:#ED1C24;color:#ffffff;border-bottom:1px solid #ED1C24;padding:9px 8px;text-align:center;font-weight:700;text-transform:uppercase;font-size:10px;" bgcolor="#ED1C24">Total</th>
          </tr>
        </thead>
        <tbody>
          @forelse(($receipt_details->lines ?? []) as $index => $line)
            @php
              $desc = trim(($line['name'] ?? '').' '.($line['variation'] ?? ''));
              $qty = ($line['quantity'] ?? '').(! empty($line['units']) ? ' '.$line['units'] : '');
              $unitPrice = $line['unit_price_inc_tax'] ?? $line['unit_price'] ?? '';
              $lineTotal = $line['line_total'] ?? '';
              $isLast = $loop->last;
            @endphp
            <tr>
              <td style="background-color:#FFF5F5;color:#111111;border-right:1px solid #ED1C24;{{ $isLast ? '' : 'border-bottom:1px solid #ED1C24;' }}padding:9px 8px;text-align:center;" bgcolor="#FFF5F5">{{ $index + 1 }}</td>
              <td class="desc" style="background-color:#FFF5F5;color:#111111;border-right:1px solid #ED1C24;{{ $isLast ? '' : 'border-bottom:1px solid #ED1C24;' }}padding:9px 8px;text-align:left;" bgcolor="#FFF5F5">
                {{ $desc }}
                @if(! empty($line['product_description']))
                  <div style="font-size:10px;margin-top:3px;color:#6b7280;">{!! strip_tags($line['product_description']) !!}</div>
                @endif
              </td>
              <td style="background-color:#FFF5F5;color:#111111;border-right:1px solid #ED1C24;{{ $isLast ? '' : 'border-bottom:1px solid #ED1C24;' }}padding:9px 8px;text-align:center;" bgcolor="#FFF5F5">{{ $qty }}</td>
              <td class="money" style="background-color:#FFF5F5;color:#111111;border-right:1px solid #ED1C24;{{ $isLast ? '' : 'border-bottom:1px solid #ED1C24;' }}padding:9px 8px;text-align:right;white-space:nowrap;" bgcolor="#FFF5F5">{{ $unitPrice }}</td>
              <td class="money" style="background-color:#FFF5F5;color:#111111;{{ $isLast ? '' : 'border-bottom:1px solid #ED1C24;' }}padding:9px 8px;text-align:right;white-space:nowrap;" bgcolor="#FFF5F5">{{ $lineTotal }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" style="background-color:#FFF5F5;padding:9px 8px;" bgcolor="#FFF5F5">No items</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <table class="summary-row" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin-top:6px;">
      <tr>
        <td class="pay-col" width="52%" valign="top" style="width:52%;vertical-align:top;padding:0 12px 0 0;border:none;">
          @if($bankText !== '')
            <table width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;border:1.5px solid #ED1C24;background-color:#FCE8E9;">
              <tr>
                <td colspan="2" style="color:#ED1C24;font-size:13px;font-weight:800;padding:10px 12px 6px 12px;background-color:#FCE8E9;">Bank Details</td>
              </tr>
              @if(! empty($paymentModeLine))
              <tr>
                <td colspan="2" style="font-size:11px;padding:0 12px 6px 12px;background-color:#FCE8E9;"><strong>Payment mode:</strong> {{ $paymentModeLine }}</td>
              </tr>
              @endif
              @forelse($bankRows as $row)
                <tr>
                  @if($row['label'] !== '')
                    <td width="38%" style="font-weight:700;color:#111111;font-size:11px;padding:3px 4px 3px 12px;vertical-align:top;background-color:#FCE8E9;white-space:nowrap;">{{ $row['label'] }}:</td>
                    <td style="color:#333333;font-size:11px;padding:3px 12px 3px 4px;vertical-align:top;background-color:#FCE8E9;">{{ $row['value'] }}</td>
                  @else
                    <td colspan="2" style="color:#333333;font-size:11px;padding:3px 12px;background-color:#FCE8E9;">{{ $row['value'] }}</td>
                  @endif
                </tr>
              @empty
                <tr>
                  <td colspan="2" style="color:#333333;font-size:11px;padding:3px 12px 10px 12px;background-color:#FCE8E9;">{!! nl2br(e($bankText)) !!}</td>
                </tr>
              @endforelse
              @if(count($bankRows) > 0)
              <tr>
                <td colspan="2" style="padding:0 0 8px 0;background-color:#FCE8E9;font-size:1px;line-height:1px;">&nbsp;</td>
              </tr>
              @endif
            </table>
          @endif
        </td>
        <td class="total-col" width="48%" valign="top" style="width:48%;vertical-align:top;padding:0 0 0 4px;border:none;">
          @php
            $taxLabel = trim(str_replace(':', '', (string) ($receipt_details->tax_label ?? 'VAT')));
            if ($taxLabel === '' || strcasecmp($taxLabel, 'Tax') === 0) {
                $taxLabel = 'VAT';
            }
            $taxValue = $receipt_details->tax ?? null;
            if ($taxValue === null || $taxValue === '' || $taxValue === 0 || $taxValue === '0') {
                $sym = $receipt_details->currency['symbol'] ?? 'LKR';
                $taxValue = $sym.' 0.00';
            }
            $hasPaid = ! empty($receipt_details->total_paid) && $receipt_details->total_paid != 0 && $receipt_details->total_paid != '0.00';
            $showPaidBlock = ! $isQuotation && isset($receipt_details->total_paid);
          @endphp
          <table class="totals-table" width="100%" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:12px;">
            @if(! empty($receipt_details->subtotal))
            <tr>
              <td class="label" style="text-align:left;color:#333333;padding:4px 2px;">Subtotal</td>
              <td class="value" style="text-align:right;white-space:nowrap;color:#111111;padding:4px 2px;">{{ $receipt_details->subtotal }}</td>
            </tr>
            @endif
            @if(! empty($receipt_details->discount))
            <tr>
              <td class="label" style="text-align:left;color:#333333;padding:4px 2px;">{{ trim(str_replace(':', '', (string) ($receipt_details->discount_label ?? 'Discount'))) }}</td>
              <td class="value" style="text-align:right;white-space:nowrap;color:#111111;padding:4px 2px;">(-) {{ $receipt_details->discount }}</td>
            </tr>
            @endif
            <tr>
              <td class="label" style="text-align:left;color:#333333;padding:4px 2px;">{{ $taxLabel }}</td>
              <td class="value" style="text-align:right;white-space:nowrap;color:#111111;padding:4px 2px;">{{ $taxValue }}</td>
            </tr>
            @if(! empty($receipt_details->shipping_charges))
            <tr>
              <td class="label" style="text-align:left;color:#333333;padding:4px 2px;">{{ trim(str_replace(':', '', (string) ($receipt_details->shipping_charges_label ?? 'Shipping'))) }}</td>
              <td class="value" style="text-align:right;white-space:nowrap;color:#111111;padding:4px 2px;">{{ $receipt_details->shipping_charges }}</td>
            </tr>
            @endif
            <tr class="rule"><td colspan="2" style="padding:0;border-top:1.5px solid #111111;height:0;font-size:0;line-height:0;">&nbsp;</td></tr>
            <tr class="grand">
              <td class="label" style="text-align:left;font-weight:800;font-size:13px;color:#111111;padding:6px 2px;">Total (LKR)</td>
              <td class="value" style="text-align:right;white-space:nowrap;font-weight:800;font-size:13px;color:#111111;padding:6px 2px;">{{ $receipt_details->total ?? '' }}</td>
            </tr>
            @if($showPaidBlock)
              @if($hasPaid)
              <tr class="paid">
                <td class="label" style="text-align:left;color:#16a34a;font-weight:700;padding:4px 2px;">Paid to Date</td>
                <td class="value" style="text-align:right;white-space:nowrap;color:#16a34a;font-weight:700;padding:4px 2px;">- {{ $receipt_details->total_paid }}</td>
              </tr>
              @endif
              <tr class="rule"><td colspan="2" style="padding:0;border-top:1.5px solid #111111;height:0;font-size:0;line-height:0;">&nbsp;</td></tr>
              <tr class="due">
                <td class="label" style="text-align:left;color:#ED1C24;font-weight:800;font-size:14px;padding:6px 2px;">Balance Due</td>
                <td class="value" style="text-align:right;white-space:nowrap;color:#ED1C24;font-weight:800;font-size:14px;padding:6px 2px;">{{ (! empty($receipt_details->total_due) && $receipt_details->total_due != 0) ? $receipt_details->total_due : (($receipt_details->currency['symbol'] ?? 'LKR').' 0.00') }}</td>
              </tr>
              <tr class="rule-double"><td colspan="2" style="padding:0;border-top:3px double #111111;height:0;font-size:0;line-height:0;">&nbsp;</td></tr>
            @endif
          </table>
        </td>
      </tr>
    </table>

    @if($paymentHistory->isNotEmpty())
      <div class="pay-history-title" style="margin-top:14px;margin-bottom:6px;font-size:11px;font-weight:800;color:#ED1C24;text-transform:uppercase;">Payment History</div>
      <table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0;font-size:10px;border:1.5px solid #ED1C24;">
        <thead>
          <tr>
            <th style="background-color:#ED1C24;color:#ffffff;border-right:1px solid #ED1C24;border-bottom:1px solid #ED1C24;padding:6px 8px;text-align:left;font-weight:700;" bgcolor="#ED1C24">Date</th>
            <th style="background-color:#ED1C24;color:#ffffff;border-right:1px solid #ED1C24;border-bottom:1px solid #ED1C24;padding:6px 8px;text-align:left;font-weight:700;" bgcolor="#ED1C24">Method</th>
            <th style="background-color:#ED1C24;color:#ffffff;border-right:1px solid #ED1C24;border-bottom:1px solid #ED1C24;padding:6px 8px;text-align:left;font-weight:700;" bgcolor="#ED1C24">Note</th>
            <th style="background-color:#ED1C24;color:#ffffff;border-bottom:1px solid #ED1C24;padding:6px 8px;text-align:right;font-weight:700;" bgcolor="#ED1C24">Amount</th>
          </tr>
        </thead>
        <tbody>
          @foreach($paymentHistory as $payment)
            @php $payLast = $loop->last; @endphp
            <tr>
              <td style="background-color:#FFF5F5;color:#111111;border-right:1px solid #ED1C24;{{ $payLast ? '' : 'border-bottom:1px solid #ED1C24;' }}padding:6px 8px;" bgcolor="#FFF5F5">{{ $payment['date'] ?? '—' }}</td>
              <td style="background-color:#FFF5F5;color:#111111;border-right:1px solid #ED1C24;{{ $payLast ? '' : 'border-bottom:1px solid #ED1C24;' }}padding:6px 8px;" bgcolor="#FFF5F5">{{ $payment['method'] ?? '—' }}</td>
              <td style="background-color:#FFF5F5;color:#111111;border-right:1px solid #ED1C24;{{ $payLast ? '' : 'border-bottom:1px solid #ED1C24;' }}padding:6px 8px;" bgcolor="#FFF5F5">{{ ! empty($payment['note']) ? $payment['note'] : '—' }}</td>
              <td style="background-color:#FFF5F5;color:#111111;{{ $payLast ? '' : 'border-bottom:1px solid #ED1C24;' }}padding:6px 8px;text-align:right;font-weight:700;white-space:nowrap;" bgcolor="#FFF5F5">{{ $payment['amount'] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif

  </div>{{-- /.content-area --}}

  {{-- Browser: pin signature just above brand footer at page bottom --}}
  @if($embedFooter)
  <div class="sign-zone">
    <table class="bottom-row">
      <tr>
        <td class="sys-msg">*This is a system generated {{ strtolower($docTitle) }}.</td>
        <td class="sign-block">
          @if(! empty($signB64))
            <img class="sign-img" src="data:image/png;base64,{{ $signB64 }}" alt="Signature">
          @endif
          <div class="sign-closing">Yours faithfully,</div>
          <div class="sign-name">Sandaruwan Dharampriya</div>
          <div class="sign-role">Director, PrintWorks</div>
        </td>
      </tr>
    </table>
  </div>
  @endif

  @if($embedFooter && ! empty($footerB64))
  <div class="page-footer">
    <img src="data:image/png;base64,{{ $footerB64 }}" alt="Footer">
  </div>
  @endif

</div>
</body>
</html>
