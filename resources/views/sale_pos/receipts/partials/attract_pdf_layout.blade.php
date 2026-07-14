@php
    $logoCandidates = [
        public_path('images/printworks_logo.png'),
        public_path('images/logo.png'),
        public_path('images/logo.jpeg'),
        public_path('images/attract_letterhead_HQ.png'),
    ];
    $logoPath = null;
    foreach ($logoCandidates as $candidate) {
        if (file_exists($candidate)) {
            $logoPath = $candidate;
            break;
        }
    }
    $footerPath = public_path('images/footer.png');
    if (! file_exists($footerPath)) {
        $footerPath = public_path('images/footer (1).png');
    }

    $logoMime = $logoPath ? (mime_content_type($logoPath) ?: 'image/png') : 'image/png';
    $logoB64 = $logoPath ? base64_encode(file_get_contents($logoPath)) : null;
    $footerB64 = file_exists($footerPath) ? base64_encode(file_get_contents($footerPath)) : null;

    $embedFooter = $embed_footer ?? true;

    $docTitle = $document_title ?? 'INVOICE';
    $isProforma = ! empty($receipt_details->is_proforma)
        || ($receipt_details->sub_status ?? '') === 'proforma'
        || strtoupper((string) ($document_title ?? '')) === 'PROFORMA INVOICE';
    $isQuotation = ! empty($receipt_details->is_quotation) && ! $isProforma;

    if ($isProforma) {
        $docTitle = 'PROFORMA INVOICE';
    } elseif ($isQuotation && empty($document_title)) {
        $docTitle = 'QUOTATION';
    }

    $invNo = (string) ($receipt_details->invoice_no ?? '');
    $primaryNoLabel = $isProforma ? 'PI NO' : ($isQuotation ? 'QUOTE NO' : 'INVOICE NO');
    $quoteNo = $isQuotation
        ? $invNo
        : (string) ($receipt_details->quotation_no ?? $receipt_details->parent_invoice_no ?? $receipt_details->quotation_ref_no ?? '');

    $customerName = trim((string) ($receipt_details->customer_name ?? 'Walk-In Customer'));
    $customerAddress = trim(strip_tags($receipt_details->customer_info_address ?? ''));
    if ($customerAddress === '' && ! empty($receipt_details->customer_info)) {
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($receipt_details->customer_info)));
        if ($customerName !== '' && str_starts_with($plain, $customerName)) {
            $plain = trim(substr($plain, strlen($customerName)));
        }
        $customerAddress = $plain;
    }
    // Remove duplicated name / phone / email from address blob
    $customerEmail = trim((string) ($receipt_details->customer_email ?? ''));
    $customerPhone = trim((string) ($receipt_details->customer_mobile ?? ''));
    if ($customerAddress !== '') {
        $parts = array_values(array_filter(array_map('trim', explode(',', $customerAddress))));
        $clean = [];
        $seen = [];
        foreach ($parts as $part) {
            $key = strtolower(preg_replace('/\s+/', ' ', $part));
            if ($key === '') {
                continue;
            }
            if ($customerName !== '' && strcasecmp($part, $customerName) === 0) {
                continue;
            }
            if ($customerPhone !== '' && preg_replace('/\D+/', '', $part) === preg_replace('/\D+/', '', $customerPhone)) {
                continue;
            }
            if ($customerEmail !== '' && strcasecmp($part, $customerEmail) === 0) {
                continue;
            }
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $clean[] = $part;
        }
        $customerAddress = implode(', ', $clean);
    }

    $invoiceDateRaw = (string) ($receipt_details->invoice_date ?? '');
    $dateDisplay = $invoiceDateRaw;
    $dayName = '';
    try {
        $dt = \Carbon\Carbon::parse(strip_tags($invoiceDateRaw));
        $dayName = strtoupper($dt->format('l'));
        $dateDisplay = $dt->format('jS F, Y');
    } catch (\Throwable $e) {
        // keep original string
    }

    $salesChannel = trim((string) ($receipt_details->types_of_service ?? ''));
    if ($salesChannel === '') {
        $salesChannel = trim((string) ($receipt_details->sales_person ?? ''));
    }
    if ($salesChannel === '') {
        $salesChannel = $isQuotation ? 'Quotation' : ($isProforma ? 'Proforma' : 'POS');
    }

    $currencySym = $receipt_details->currency['symbol'] ?? 'Rs.';
    $showDueFields = ! $isQuotation && ! $isProforma;
    $showBalanceDueHeader = ! $isQuotation;
    $grandTotalDisplay = (string) ($receipt_details->total ?? '');
    $totalDueRaw = $receipt_details->total_due ?? null;
    $totalPaid = $receipt_details->total_paid ?? ($currencySym.' 0.00');
    $dueDate = $receipt_details->due_date ?? $dateDisplay;
    $paymentStatusRaw = strtolower(trim((string) ($receipt_details->payment_status ?? '')));
    $isPaid = $showDueFields && (
        $paymentStatusRaw === 'paid'
        || (is_numeric($totalDueRaw) && (float) $totalDueRaw == 0)
        || (is_string($totalDueRaw) && preg_match('/^[\D\s]*0+([.,]0+)?[\D\s]*$/', (string) $totalDueRaw))
    );
    $hasAdvancePayment = $showDueFields
        && ! empty($totalPaid)
        && $totalPaid !== 0
        && $totalPaid !== '0'
        && ! preg_match('/^[\D\s]*0+([.,]0+)?[\D\s]*$/', (string) $totalPaid);

    $balanceDue = $currencySym.' 0.00';
    if ($isProforma && $grandTotalDisplay !== '') {
        // Proforma: payment not yet received — show full grand total as balance due
        $balanceDue = $grandTotalDisplay;
    } elseif ($showDueFields) {
        if (! empty($totalDueRaw) && $totalDueRaw !== 0 && $totalDueRaw !== '0'
            && ! preg_match('/^[\D\s]*0+([.,]0+)?[\D\s]*$/', (string) $totalDueRaw)) {
            $balanceDue = (string) $totalDueRaw;
        } elseif (! $isPaid && $grandTotalDisplay !== '') {
            $balanceDue = $grandTotalDisplay;
        }
    }

    $showTotalDueRow = $showDueFields && (
        ! $isPaid
        || (! empty($totalDueRaw) && $totalDueRaw !== 0 && $totalDueRaw !== '0'
            && ! preg_match('/^[\D\s]*0+([.,]0+)?[\D\s]*$/', (string) $totalDueRaw))
    );

    $paymentStatus = ucfirst((string) ($receipt_details->payment_status ?? ''));
    if ($isPaid) {
        $paymentStatus = 'Paid';
    } elseif ($paymentStatus === '') {
        $paymentStatus = $isQuotation ? 'Quotation' : ($isProforma ? 'Proforma' : '—');
    }

    $bankText = trim((string) ($receipt_details->pdf_bank_details ?? ''));
    if ($bankText === '' && ! empty($receipt_details->preferred_account_details)) {
        $acc = (array) $receipt_details->preferred_account_details;
        $bankLines = [];
        if (! empty($acc['account_holder_name'])) {
            $bankLines[] = $acc['account_holder_name'];
        }
        if (! empty($acc['account_number'])) {
            $bankLines[] = 'Account No : '.$acc['account_number'];
        }
        if (! empty($acc['bank_name']) || ! empty($acc['branch'])) {
            $bankLines[] = trim(($acc['bank_name'] ?? '').(! empty($acc['branch']) ? ' - '.$acc['branch'] : ''));
        }
        if (! empty($acc['swift_code'])) {
            $bankLines[] = 'Swift Code : '.$acc['swift_code'];
        }
        $bankText = implode("\n", $bankLines);
    }
    if ($bankText === '') {
        $bankText = (string) config('constants.default_pdf_bank_details');
    }

    // Normalize into neat display lines (mockup style)
    $bankDisplayLines = [];
    $paymentModeLine = 'cash / cheque / bank transfer';
    foreach (preg_split("/\r\n|\n|\r/", $bankText) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (preg_match('/^payment\s*mode\s*:?\s*(.+)$/i', $line, $m)) {
            $paymentModeLine = trim($m[1]);
            continue;
        }
        // Strip redundant "Company:" / "Bank/Branch:" prefixes for clean look
        if (preg_match('/^(company|account\s*holder)\s*:?\s*(.+)$/i', $line, $m)) {
            $bankDisplayLines[] = trim($m[2]);
            continue;
        }
        if (preg_match('/^bank\s*\/?\s*branch\s*:?\s*(.+)$/i', $line, $m)) {
            $bankDisplayLines[] = trim($m[1]);
            continue;
        }
        if (preg_match('/^account\s*no\.?\s*:?\s*(.+)$/i', $line, $m)) {
            $bankDisplayLines[] = 'Account No : '.trim($m[1]);
            continue;
        }
        if (preg_match('/^swift\s*code\s*:?\s*(.+)$/i', $line, $m)) {
            $bankDisplayLines[] = 'Swift Code : '.trim($m[1]);
            continue;
        }
        $bankDisplayLines[] = $line;
    }
    $bankDisplayLines[] = 'Payment mode : '.$paymentModeLine;

    $quotationTermsText = trim((string) ($receipt_details->quotation_terms ?? ''));
    if ($quotationTermsText === '' && $isQuotation) {
        $quotationTermsText = (string) config('constants.default_quotation_terms');
    }
    $quotationTermLines = array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", $quotationTermsText))));
    $additionalNoteLines = array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", (string) ($receipt_details->additional_notes ?? '')))));
    $additionalTermsSections = \App\Utils\QuotationAdditionalTermsUtil::sectionsForPdf(
        $receipt_details->quotation_additional_terms ?? null
    );

    $validTillDisplay = '—';
    if (! empty($receipt_details->quotation_valid_till)) {
        try {
            $validTillDisplay = \Carbon\Carbon::parse($receipt_details->quotation_valid_till)->format('jS F, Y');
        } catch (\Throwable $e) {
            $validTillDisplay = (string) $receipt_details->quotation_valid_till;
        }
    } elseif ($isQuotation && $dateDisplay !== '') {
        try {
            $validTillDisplay = \Carbon\Carbon::parse(strip_tags($invoiceDateRaw))->addDays(7)->format('jS F, Y');
        } catch (\Throwable $e) {
            $validTillDisplay = $dateDisplay;
        }
    }

    $preparedByName = trim((string) ($receipt_details->prepared_by_name ?? $receipt_details->added_by ?? $receipt_details->sales_person ?? ''));
    if ($preparedByName === '') {
        $preparedByName = 'Name';
    }

    $brandRed = '#E31E24';
    $rowGrey = '#E8E8E8';
    $lines = collect($receipt_details->lines ?? [])->values();
    $itemCount = $lines->count();

    // Exact mockup sizes — do not scale fonts by item count.
    $fs = static fn (float $base): string => round($base, 1).'px';
    $sp = static fn (float $base): string => round($base, 1).'px';
    $itemRowMinHeight = '22px';

    $shadowRed = $embedFooter ? "box-shadow:inset 0 0 0 1000px {$brandRed} !important;" : '';
    $shadowGrey = $embedFooter ? "box-shadow:inset 0 0 0 1000px {$rowGrey} !important;" : '';

    // Browser print needs data-URI; mPDF prefers absolute filesystem path.
    $logoSrc = null;
    if ($logoPath && file_exists($logoPath)) {
        $logoSrc = $embedFooter
            ? ('data:'.$logoMime.';base64,'.$logoB64)
            : $logoPath;
    } elseif (! empty($logoB64)) {
        $logoSrc = 'data:'.$logoMime.';base64,'.$logoB64;
    }

    $discountRaw = $receipt_details->discount_amount_unformatted ?? null;
    $discountDisplay = $receipt_details->discount ?? null;
    $hasDiscount = (is_numeric($discountRaw) && (float) $discountRaw != 0)
        || (! empty($discountDisplay) && $discountDisplay !== 0 && $discountDisplay !== '0'
            && ! preg_match('/^[\D\s]*0+([.,]0+)?[\D\s]*$/', (string) $discountDisplay));
    $discountShow = $hasDiscount ? $discountDisplay : ($currencySym.' 0.00');
    $advanceShow = ($hasAdvancePayment ? $totalPaid : ($currencySym.' 0.00'));
    $statusDisplay = $paymentStatus;
    if ($showDueFields && ! empty($receipt_details->pay_term_number) && ! empty($receipt_details->pay_term_type)) {
        $statusDisplay = trim($receipt_details->pay_term_number.' '.$receipt_details->pay_term_type.(str_contains(strtolower((string) $receipt_details->pay_term_type), 'day') ? '' : ''));
        if (is_numeric($receipt_details->pay_term_number) && strtolower((string) $receipt_details->pay_term_type) === 'days') {
            $statusDisplay = ((int) $receipt_details->pay_term_number).' Days credit';
        } elseif (is_numeric($receipt_details->pay_term_number) && strtolower((string) $receipt_details->pay_term_type) === 'months') {
            $statusDisplay = ((int) $receipt_details->pay_term_number).' Months credit';
        }
    }
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $docTitle }} {{ $invNo }}</title>
<style>
  @if($embedFooter)
  @page { margin: 10mm 0 0 0; size: A4; color-adjust: exact; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  @else
  @page { margin: 0; }
  @endif
  html, body {
    background: #fff !important;
    margin: 0;
    padding: 0;
    font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
    color: #111;
    font-size: {{ $fs(11) }};
  }
  html, body, *, *::before, *::after,
  table, thead, tbody, tr, th, td, div, span {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    color-adjust: exact !important;
  }
  .sheet {
    width: 100%;
    position: relative;
    @if($embedFooter)
    min-height: 277mm;
    box-sizing: border-box;
    padding: 0 12mm 48mm 12mm;
    @else
    /* mPDF page has 0 side margins so content padding keeps text inset; footer stays full-bleed */
    box-sizing: border-box;
    padding: 0 12mm;
    @endif
  }
  .paid-watermark {
    position: absolute;
    top: 38%;
    left: 0;
    width: 100%;
    text-align: center;
    font-size: 92px;
    font-weight: 800;
    color: {{ $brandRed }};
    opacity: 0.16;
    letter-spacing: 8px;
    transform: rotate(-32deg);
    -webkit-transform: rotate(-32deg);
    z-index: 50;
    pointer-events: none;
    line-height: 1;
  }
  .paid-stamp {
    display: inline-block;
    margin-top: 6px;
    padding: 3px 10px;
    border: 2px solid {{ $brandRed }};
    color: {{ $brandRed }} !important;
    font-weight: 800;
    font-size: 12px;
    letter-spacing: 1px;
    text-transform: uppercase;
  }
  .header-table { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
  .header-table td { vertical-align: top; border: none; padding: 0; }
  .logo-wrap img {
    max-height: 52px;
    max-width: 200px;
    width: auto;
    height: auto;
    display: block;
  }
  .brand-tag {
    font-size: 9px;
    color: #333;
    margin-top: 4px;
    line-height: 1.35;
  }
  .doc-title {
    font-size: 36px;
    font-weight: 800;
    color: #111;
    letter-spacing: 1px;
    text-align: right;
    line-height: 1;
    margin: 0 0 8px 0;
    text-transform: uppercase;
  }
  .meta-table { width: auto; margin-left: auto; border-collapse: collapse; font-size: 11px; }
  .meta-table td { padding: 1px 0; border: none; vertical-align: top; }
  .meta-table td.lbl { text-align: left; white-space: nowrap; padding-right: 2px; font-weight: 700; color: #111; }
  .meta-table td.sep { width: 10px; text-align: center; padding: 1px 5px; font-weight: 700; }
  .meta-table td.val { text-align: left; font-weight: 700; padding-left: 2px; }
  .meta-table .accent { color: {{ $brandRed }} !important; }
  .meta-table .day {
    color: {{ $brandRed }} !important;
    font-weight: 800;
    font-size: 12px;
    letter-spacing: 0.5px;
  }
  .bill-to { margin: 14px 0 12px 0; }
  .bill-to .title { color: {{ $brandRed }} !important; font-weight: 800; font-size: 13px; margin-bottom: 5px; }
  .bill-to .line { margin: 2px 0; color: #222; font-size: 11px; line-height: 1.45; }
  .items { width: 100%; border-collapse: collapse; margin-top: 6px; }
  .items th {
    background-color: {{ $brandRed }} !important;
    background: {{ $brandRed }} !important;
    @if($embedFooter)
    box-shadow: inset 0 0 0 1000px {{ $brandRed }} !important;
    @endif
    color: #ffffff !important;
    font-weight: 700;
    font-size: 11px;
    padding: 8px 5px;
    text-align: center;
    border: none;
  }
  .items th.desc { text-align: left; padding-left: 8px; }
  .items td {
    border-bottom: 1px solid #d0d0d0;
    padding: 8px 5px;
    vertical-align: middle;
    font-size: 11px;
    min-height: {{ $itemRowMinHeight }};
    height: {{ $itemRowMinHeight }};
  }
  /* Quotation: Rate / Discount / Total = grey. Invoice: Rate / Qty / Discount / Total = grey */
  .items td.num {
    text-align: center;
    width: 28px;
    background: #fff !important;
    background-color: #fff !important;
  }
  .items td.desc { text-align: left; background: #fff !important; background-color: #fff !important; }
  .items td.qty {
    text-align: center;
    @if($isQuotation)
    background: #fff !important;
    background-color: #fff !important;
    @else
    background-color: {{ $rowGrey }} !important;
    background: {{ $rowGrey }} !important;
    @if($embedFooter)
    box-shadow: inset 0 0 0 1000px {{ $rowGrey }} !important;
    @endif
    @endif
  }
  .items td.money,
  .items td.disc,
  .items td.total {
    text-align: center;
    white-space: nowrap;
    background-color: {{ $rowGrey }} !important;
    background: {{ $rowGrey }} !important;
    @if($embedFooter)
    box-shadow: inset 0 0 0 1000px {{ $rowGrey }} !important;
    @endif
  }
  .prod-name { font-weight: 700; color: #111; font-size: 11px; }
  .prod-note { margin-top: 3px; color: #444; font-size: 9.5px; line-height: 1.45; word-wrap: break-word; }
  .bottom { width: 100%; border-collapse: collapse; margin-top: 16px; }
  .bottom > td { vertical-align: top; border: none; padding: 0; }
  .bank-title {
    color: {{ $brandRed }} !important;
    font-weight: 800;
    font-size: 13px;
    margin: 0 0 8px 0;
    letter-spacing: 0.2px;
  }
  .bank-lines {
    font-size: 11px;
    color: #111;
    line-height: 1.6;
  }
  .bank-lines .bank-line {
    margin: 0 0 4px 0;
    font-weight: 600;
  }
  .sign-block {
    margin-top: 18px;
    font-size: 11px;
    line-height: 1.75;
    color: #111;
  }
  .sys-note {
    font-weight: 700;
    font-size: 11px;
    margin: 0 0 8px 0;
    color: #111;
  }
  .sign-line {
    margin: 0 0 3px 0;
    font-weight: 500;
  }
  .tagline {
    margin-top: 10px;
    color: {{ $brandRed }} !important;
    font-style: italic;
    font-size: 11px;
  }
  .totals { width: 100%; border-collapse: separate; border-spacing: 0 4px; font-size: 11px; }
  .totals-wrap {
    width: 100%;
    margin-top: 8px;
    margin-bottom: 14px;
  }
  .totals-wrap .totals {
    width: 48%;
    margin-left: auto;
    margin-right: 0;
  }
  .totals.totals-inline {
    width: 100%;
    margin: 0;
  }
  .totals td {
    padding: 8px 10px;
    border: none;
    background-color: {{ $rowGrey }} !important;
    background: {{ $rowGrey }} !important;
    @if($embedFooter)
    box-shadow: inset 0 0 0 1000px {{ $rowGrey }} !important;
    @endif
  }
  .totals td.lbl { text-align: left; font-weight: 800; width: 55%; }
  .totals td.val { text-align: right; white-space: nowrap; font-weight: 800; }
  .totals tr.grand td {
    background-color: {{ $brandRed }} !important;
    background: {{ $brandRed }} !important;
    @if($embedFooter)
    box-shadow: inset 0 0 0 1000px {{ $brandRed }} !important;
    @endif
    color: #ffffff !important;
    font-weight: 800;
    font-size: 12px;
    padding: 9px 10px;
  }
  .terms-title { color: {{ $brandRed }} !important; font-weight: 800; font-size: 13px; margin: 0 0 6px 0; }
  .terms-list { margin: 0; padding-left: 16px; font-size: 11px; line-height: 1.65; color: #111; }
  .terms-list li { margin: 0 0 4px 0; }
  .quote-meta { margin-top: 12px; font-size: 11px; line-height: 1.65; }
  .quote-meta .lbl { font-weight: 700; }
  .due-meta { margin-top: 10px; font-size: 11px; line-height: 1.65; }
  .due-meta .lbl { font-weight: 700; }
  .page-break-before {
    page-break-before: always;
    break-before: page;
  }
  .additional-terms-page {
    padding-top: {{ $sp(8) }};
  }
  .additional-terms-page-header {
    border-bottom: 2px solid {{ $brandRed }};
    padding-bottom: {{ $sp(10) }};
    margin-bottom: {{ $sp(16) }};
  }
  .additional-terms-page-title {
    color: {{ $brandRed }} !important;
    font-weight: 800;
    font-size: {{ $fs(16) }};
    margin: 0 0 {{ $sp(4) }} 0;
    letter-spacing: 0.3px;
  }
  .additional-terms-page-sub {
    font-size: {{ $fs(10.5) }};
    color: #666;
    margin: 0;
  }
  .additional-terms-card {
    border: 1px solid #E8E8E8;
    border-left: 4px solid {{ $brandRed }};
    background: #fafafa;
    padding: {{ $sp(12) }} {{ $sp(14) }};
    margin: 0 0 {{ $sp(14) }} 0;
    page-break-inside: avoid;
  }
  .additional-section-title {
    color: {{ $brandRed }} !important;
    font-weight: 800;
    font-size: {{ $fs(11.5) }};
    margin: 0 0 {{ $sp(8) }} 0;
    text-transform: uppercase;
    letter-spacing: 0.2px;
  }
  .additional-section-body {
    margin: 0;
    font-size: {{ $fs(10.5) }};
    line-height: 1.65;
    color: #222;
  }
  .additional-section-body p {
    margin: 0 0 {{ $sp(8) }} 0;
  }
  .additional-section-body p:last-child {
    margin-bottom: 0;
  }
  .page-footer {
    @if($embedFooter)
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    margin: 0 !important;
    padding: 0 !important;
    @else
    margin-top: 14px;
    @endif
  }
  .page-footer img {
    width: 100% !important;
    max-width: none !important;
    height: auto;
    display: block;
  }
  .footer-bar {
    @if($embedFooter)
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    margin: 0 !important;
    @else
    margin-top: 10px;
    @endif
    border-top: 2px solid #5b1a3a;
    padding-top: 8px;
    font-size: 9px;
    color: #444;
    text-align: center;
    line-height: 1.45;
  }
  @media print {
    html, body, *, *::before, *::after,
    table, thead, tbody, tr, th, td, div, span {
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
      color-adjust: exact !important;
    }
    @if($embedFooter)
    .sheet {
      min-height: 277mm;
      padding: 0 12mm 40mm 12mm !important;
      box-sizing: border-box;
    }
    .page-footer,
    .footer-bar {
      position: fixed !important;
      left: 0 !important;
      right: 0 !important;
      bottom: 0 !important;
      width: 210mm !important;
      max-width: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
    }
    .page-footer img {
      width: 210mm !important;
      max-width: 100% !important;
      height: auto !important;
      display: block !important;
    }
    @endif
  }
</style>
</head>
<body>
<div class="sheet">
  @if($isPaid)
    <div class="paid-watermark">PAID</div>
  @endif

  <table class="header-table">
    <tr>
      <td style="width:48%;">
        <div class="logo-wrap">
          @if($logoSrc)
            <img src="{{ $logoSrc }}" alt="printworks.lk">
          @else
            <div style="font-size:24px;font-weight:800;color:#111;">printworks<span style="color:{{ $brandRed }};">.lk</span></div>
            <div style="font-size:10px;color:#666;">promotional &amp; branding solutions</div>
          @endif
        </div>
        <div class="brand-tag">A trademark of Attract Wear &amp; Printing Solutions</div>

        <div class="bill-to">
          <div class="title">{{ $isQuotation ? 'Quotation To' : 'Bill To' }}</div>
          <div class="line">{{ $customerName !== '' ? $customerName : 'Client Name Here' }}</div>
          <div class="line">Address : {{ $customerAddress !== '' ? $customerAddress : '—' }}</div>
          <div class="line">Email : {{ $customerEmail !== '' ? $customerEmail : '—' }}</div>
          <div class="line">Phone number : {{ $customerPhone !== '' ? $customerPhone : '—' }}</div>
        </div>
      </td>
      <td style="width:52%;text-align:right;">
        <div class="doc-title">{{ $docTitle }}</div>
        <table class="meta-table" align="right">
          <tr>
            <td class="lbl">{{ $primaryNoLabel }}</td>
            <td class="sep">:</td>
            <td class="val">{{ $invNo !== '' ? $invNo : '—' }}</td>
          </tr>
          <tr>
            <td class="lbl" colspan="3" style="padding-top:4px;">
              @if($dayName !== '')
                <span class="day">{{ $dayName }}</span>
              @else
                Date
              @endif
            </td>
          </tr>
          <tr>
            <td class="val" colspan="3" style="padding-bottom:3px;">{{ $dateDisplay !== '' ? $dateDisplay : '—' }}</td>
          </tr>
          @if(! $isQuotation)
          <tr>
            <td class="lbl">Quote No</td>
            <td class="sep">:</td>
            <td class="val accent">{{ $quoteNo !== '' ? $quoteNo : '—' }}</td>
          </tr>
          @endif
          <tr>
            <td class="lbl">Sales Channel</td>
            <td class="sep">:</td>
            <td class="val accent">{{ $salesChannel }}</td>
          </tr>
          @if($showBalanceDueHeader)
          <tr>
            <td class="lbl">Balance Due</td>
            <td class="sep">:</td>
            <td class="val accent">{{ $balanceDue }}</td>
          </tr>
          @endif
        </table>
      </td>
    </tr>
  </table>

  <table class="items" cellpadding="0" cellspacing="0">
    <thead>
      <tr>
        <th bgcolor="{{ $brandRed }}" style="width:5%;background-color:{{ $brandRed }} !important;{{ $shadowRed }}color:#fff !important;">#</th>
        <th class="desc" bgcolor="{{ $brandRed }}" style="width:42%;background-color:{{ $brandRed }} !important;{{ $shadowRed }}color:#fff !important;text-align:left;padding-left:8px;">Description</th>
        <th bgcolor="{{ $brandRed }}" style="width:13%;background-color:{{ $brandRed }} !important;{{ $shadowRed }}color:#fff !important;">Rate</th>
        <th bgcolor="{{ $brandRed }}" style="width:11%;background-color:{{ $brandRed }} !important;{{ $shadowRed }}color:#fff !important;">Qty.</th>
        <th bgcolor="{{ $brandRed }}" style="width:14%;background-color:{{ $brandRed }} !important;{{ $shadowRed }}color:#fff !important;">Discount</th>
        <th bgcolor="{{ $brandRed }}" style="width:15%;background-color:{{ $brandRed }} !important;{{ $shadowRed }}color:#fff !important;">Total</th>
      </tr>
    </thead>
    <tbody>
      @forelse($lines as $i => $line)
        @php
          $descName = trim(($line['name'] ?? '').(! empty($line['variation']) ? ' - '.$line['variation'] : ''));
          $descNote = trim(html_entity_decode(strip_tags($line['sell_line_note'] ?? ''), ENT_QUOTES, 'UTF-8'));
          if ($descNote === '' && ! empty($line['product_description'])) {
              $descNote = trim(html_entity_decode(strip_tags($line['product_description']), ENT_QUOTES, 'UTF-8'));
          }
          $rate = $line['unit_price_before_discount'] ?? $line['unit_price_inc_tax'] ?? $line['unit_price'] ?? '';
          $qty = ($line['quantity'] ?? '').(! empty($line['units']) ? ' '.$line['units'] : '');
          $discVal = $line['total_line_discount'] ?? $line['line_discount'] ?? '';
          $disc = ($discVal !== '' && $discVal !== '0' && $discVal !== '0.00') ? $discVal : '—';
          $total = $line['line_total'] ?? '';
        @endphp
        <tr>
          <td class="num" bgcolor="#ffffff" style="background-color:#ffffff !important;">{{ $i + 1 }}</td>
          <td class="desc" bgcolor="#ffffff" style="background-color:#ffffff !important;">
            <div class="prod-name">{{ $descName }}</div>
            @if($descNote !== '')
              <div class="prod-note">{!! nl2br(e($descNote)) !!}</div>
            @endif
          </td>
          <td class="money" bgcolor="{{ $rowGrey }}" style="background-color:{{ $rowGrey }} !important;{{ $shadowGrey }}">{{ $rate }}</td>
          @if($isQuotation)
          <td class="qty" bgcolor="#ffffff" style="background-color:#ffffff !important;">{{ $qty }}</td>
          @else
          <td class="qty" bgcolor="{{ $rowGrey }}" style="background-color:{{ $rowGrey }} !important;{{ $shadowGrey }}">{{ $qty }}</td>
          @endif
          <td class="disc" bgcolor="{{ $rowGrey }}" style="background-color:{{ $rowGrey }} !important;{{ $shadowGrey }}">{{ $disc }}</td>
          <td class="total" bgcolor="{{ $rowGrey }}" style="background-color:{{ $rowGrey }} !important;{{ $shadowGrey }}">{{ $total }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="padding:10px;text-align:center;color:#888;">No items</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <table class="bottom">
    <tr>
      <td style="width:52%;padding-right:16px;">
        @if($isQuotation)
          <div class="terms-title">Terms &amp; Conditions</div>
          <ul class="terms-list">
            @forelse($quotationTermLines as $term)
              <li>{{ $term }}</li>
            @empty
              <li>—</li>
            @endforelse
          </ul>
          <div class="terms-title" style="margin-top:14px;">Additional Notes</div>
          <ul class="terms-list">
            @forelse($additionalNoteLines as $note)
              <li>{{ $note }}</li>
            @empty
              <li>—</li>
            @endforelse
          </ul>
        @else
          <div class="bank-title">Bank details</div>
          <div class="bank-lines">
            @foreach($bankDisplayLines as $bankLine)
              <div class="bank-line">{{ $bankLine }}</div>
            @endforeach
          </div>

          <div class="sign-block">
            @if($embedFooter)
              <div class="sys-note">System-generated invoice. No signature required.</div>
            @endif
            <div class="sign-line">Prepared by: {{ $preparedByName }}</div>
            <div class="sign-line">Items received in good condition.</div>
            <div class="sign-line">Received by: Date .............................. Signature ..............................</div>
            @if($embedFooter)
              <div class="tagline">“Every print tells a story. Thank you for making us part of yours.”</div>
            @endif
          </div>
        @endif
      </td>
      <td style="width:48%;vertical-align:top;">
        @if($isQuotation)
          <table class="totals totals-inline" cellspacing="0" cellpadding="0">
            <tr>
              <td class="lbl" bgcolor="{{ $rowGrey }}" style="background-color:{{ $rowGrey }} !important;{{ $shadowGrey }}">Sub Total</td>
              <td class="val" bgcolor="{{ $rowGrey }}" style="background-color:{{ $rowGrey }} !important;{{ $shadowGrey }}">{{ $receipt_details->subtotal ?? ($currencySym.' 0.00') }}</td>
            </tr>
            <tr>
              <td class="lbl" bgcolor="{{ $rowGrey }}" style="background-color:{{ $rowGrey }} !important;{{ $shadowGrey }}">Total Discount</td>
              <td class="val" bgcolor="{{ $rowGrey }}" style="background-color:{{ $rowGrey }} !important;{{ $shadowGrey }}">{{ $discountShow }}</td>
            </tr>
            <tr class="grand">
              <td class="lbl" bgcolor="{{ $brandRed }}" style="background-color:{{ $brandRed }} !important;{{ $shadowRed }}color:#fff !important;">Grand Total</td>
              <td class="val" bgcolor="{{ $brandRed }}" style="background-color:{{ $brandRed }} !important;{{ $shadowRed }}color:#fff !important;">{{ $receipt_details->total ?? ($currencySym.' 0.00') }}</td>
            </tr>
          </table>
          <div class="quote-meta">
            <div><span class="lbl">Valid till</span> : {{ $validTillDisplay }}</div>
            <div><span class="lbl">Prepared by</span> : {{ $preparedByName }}</div>
            @if($embedFooter)
              <div class="sys-note" style="margin-top:10px;">System generated Quotation. No signature required.</div>
              <div class="tagline">“Committed to excellence with every project.”</div>
            @endif
          </div>
        @else
          <table class="totals totals-inline" cellspacing="0" cellpadding="0">
            <tr>
              <td class="lbl" bgcolor="{{ $rowGrey }}" style="background-color:{{ $rowGrey }} !important;{{ $shadowGrey }}">Sub Total</td>
              <td class="val" bgcolor="{{ $rowGrey }}" style="background-color:{{ $rowGrey }} !important;{{ $shadowGrey }}">{{ $receipt_details->subtotal ?? ($currencySym.' 0.00') }}</td>
            </tr>
            <tr>
              <td class="lbl" bgcolor="{{ $rowGrey }}" style="background-color:{{ $rowGrey }} !important;{{ $shadowGrey }}">Total Discount</td>
              <td class="val" bgcolor="{{ $rowGrey }}" style="background-color:{{ $rowGrey }} !important;{{ $shadowGrey }}">{{ $discountShow }}</td>
            </tr>
            <tr>
              <td class="lbl" bgcolor="{{ $rowGrey }}" style="background-color:{{ $rowGrey }} !important;{{ $shadowGrey }}">Advance Payment</td>
              <td class="val" bgcolor="{{ $rowGrey }}" style="background-color:{{ $rowGrey }} !important;{{ $shadowGrey }}">{{ $advanceShow }}</td>
            </tr>
            <tr class="grand">
              <td class="lbl" bgcolor="{{ $brandRed }}" style="background-color:{{ $brandRed }} !important;{{ $shadowRed }}color:#fff !important;">Grand Total</td>
              <td class="val" bgcolor="{{ $brandRed }}" style="background-color:{{ $brandRed }} !important;{{ $shadowRed }}color:#fff !important;">{{ $receipt_details->total ?? ($currencySym.' 0.00') }}</td>
            </tr>
          </table>
          <div class="due-meta">
            @if($showDueFields)
              <div><span class="lbl">Due</span> : {{ $dueDate }}</div>
              <div><span class="lbl">Status</span> : {{ $statusDisplay }}</div>
              @if($isPaid)
                <div class="paid-stamp">PAID</div>
              @endif
            @elseif($isProforma)
              <div><span class="lbl">Prepared by</span> : {{ $preparedByName }}</div>
            @endif
          </div>
        @endif
      </td>
    </tr>
  </table>

  @if($embedFooter && ! empty($footerB64))
    <div class="page-footer">
      <img src="data:image/png;base64,{{ $footerB64 }}" alt="Footer">
    </div>
  @elseif($embedFooter)
    <div class="footer-bar">
      Attract Wear &amp; Printing Solutions<br>
      1st Floor, No. 210/15, New Kandy Road, Biyagama, Sri Lanka<br>
      Voice: 070 666 8885 &nbsp;|&nbsp; Email: sales@printworks.lk &nbsp;|&nbsp; Web: www.printworks.lk
    </div>
  @endif

</div>

@if($isQuotation && ! empty($additionalTermsSections))
<div class="sheet page-break-before additional-terms-page">
  <div class="additional-terms-page-header">
    <div class="additional-terms-page-title">Additional Terms &amp; Conditions</div>
    <p class="additional-terms-page-sub">
      Quote No : {{ $invNo !== '' ? $invNo : '—' }}
      &nbsp;|&nbsp;
      {{ $dateDisplay !== '' ? $dateDisplay : '' }}
    </p>
  </div>

  @foreach($additionalTermsSections as $section)
    <div class="additional-terms-card">
      <div class="additional-section-title">{{ $section['title'] }}</div>
      <div class="additional-section-body">
        @foreach($section['paragraphs'] as $paragraph)
          <p>{{ $paragraph }}</p>
        @endforeach
      </div>
    </div>
  @endforeach

  @if($embedFooter && ! empty($footerB64))
    <div class="page-footer">
      <img src="data:image/png;base64,{{ $footerB64 }}" alt="Footer">
    </div>
  @elseif($embedFooter)
    <div class="footer-bar">
      Attract Wear &amp; Printing Solutions<br>
      1st Floor, No. 210/15, New Kandy Road, Biyagama, Sri Lanka<br>
      Voice: 070 666 8885 &nbsp;|&nbsp; Email: sales@printworks.lk &nbsp;|&nbsp; Web: www.printworks.lk
    </div>
  @endif
</div>
@endif
</body>
</html>
