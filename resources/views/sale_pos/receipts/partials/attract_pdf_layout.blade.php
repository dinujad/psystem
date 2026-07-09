@php
    // Image paths
    $letterheadPath = public_path('images/attract_letterhead_HQ.png');
    $signPath       = public_path('images/sign.png');

    // Base64 encode signature image
    $signB64 = file_exists($signPath) ? base64_encode(file_get_contents($signPath)) : null;

    // Document title
    $docTitle = $document_title ?? 'INVOICE';
    $isQuotation = !empty($receipt_details->is_quotation);

    // Invoice No label
    $invNo = (string)($receipt_details->invoice_no ?? '');
    $label = $isQuotation ? 'QUOTE NO:' : 'INVOICE NO:';

    // Customer address
    $customerAddress = trim(strip_tags($receipt_details->customer_info_address ?? ''));
    if (empty($customerAddress) && !empty($receipt_details->customer_mobile)) {
        $customerAddress = 'Mobile: ' . $receipt_details->customer_mobile;
    }
    if (empty($customerAddress) && !empty($receipt_details->customer_info)) {
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($receipt_details->customer_info)));
        $cname = trim($receipt_details->customer_name ?? '');
        if ($cname !== '' && str_starts_with($plain, $cname)) {
            $plain = trim(substr($plain, strlen($cname)));
        }
        $customerAddress = $plain ?: '—';
    }

    // Bank transfer total
    $bankTransferTotal = $receipt_details->bank_transfer_total ?? null;
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  html, body { background:#fff; margin:0; padding:0; font-family:'Times New Roman', serif; color:#000; }

  .a4-sheet { width:210mm; min-height:297mm; margin:0 auto; background:#fff; }

  /* Content area — footer handled by mPDF native footer, just need side/top padding */
  .content-area { padding: 5mm 10mm 10mm 10mm; }

  .quotation-title {
    text-align:center; font-size:22px; font-weight:bold;
    text-decoration:underline; margin:10px 0 16px 0;
  }

  /* Info table */
  .info-table { width:100%; border-collapse:collapse; margin-bottom:20px; font-size:13px; }
  .info-table th, .info-table td {
    border:1px solid #000; padding:7px 10px; vertical-align:top; word-break:break-word;
  }
  .info-table th { font-weight:bold; background:#f5f5f5; width:15%; }

  /* Products table */
  .prod-table { width:100%; border-collapse:collapse; margin-bottom:22px; font-size:13px; }
  .prod-table thead th {
    border-bottom:2px solid #000; padding:7px 10px;
    text-align:center; font-weight:bold;
  }
  .prod-table tbody td { padding:7px 10px; text-align:center; vertical-align:top; }
  .prod-table tbody td.desc  { text-align:left; }
  .prod-table tbody td.money { text-align:right; white-space:nowrap; }
  .prod-table tfoot td { padding:5px 10px; text-align:right; font-weight:bold; }
  .prod-table tfoot tr.grand-total td { font-size:14px; border-top:2px solid #000; }

  /* Signature + notes */
  .sign-block { text-align:right; font-size:12px; margin-top:30px; }
  .sign-block img { height:65px; width:auto; display:block; margin-left:auto; margin-bottom:4px; }

  .payment-note { font-size:11px; margin-top:10px; line-height:1.6; }
  .sys-msg { text-align:center; font-size:11px; font-style:italic; margin:14px 0 0 0; }

</style>
</head>
<body>
<div class="a4-sheet">

  {{-- Letterhead --}}
  @if(file_exists($letterheadPath))
  <div style="margin:0; padding:0;">
    <img src="{{ $letterheadPath }}" style="width:100%; display:block;" alt="Header">
  </div>
  @endif

  <div class="content-area">

    {{-- Title --}}
    <div class="quotation-title">{{ $docTitle }}</div>

    {{-- Info Table --}}
    <table class="info-table">
      <tr>
        <th>NAME</th>
        <td>{{ $receipt_details->customer_name ?? 'Walk-In Customer' }}</td>
        <th>DATE.</th>
        <td>{{ $receipt_details->invoice_date ?? '' }}</td>
      </tr>
      <tr>
        <th>ADDRESS</th>
        <td>{{ $customerAddress }}</td>
        <th>{{ $label }}</th>
        <td>{{ $invNo }}</td>
      </tr>
    </table>

    {{-- Products Table --}}
    <table class="prod-table">
      <thead>
        <tr>
          <th style="width:9%">ITEM NO</th>
          <th style="width:48%">DESCRIPTION</th>
          <th style="width:11%">QTY</th>
          <th style="width:16%">UNIT PRICE</th>
          <th style="width:16%">TOTAL PRICE</th>
        </tr>
      </thead>
      <tbody>
        @foreach($receipt_details->lines as $index => $line)
        @php
          $desc = trim(($line['name'] ?? '') . ' ' . ($line['variation'] ?? ''));
          $qty  = ($line['quantity'] ?? '') . (!empty($line['units']) ? ' ' . $line['units'] : '');
          $unitPrice  = $line['unit_price_inc_tax'] ?? $line['unit_price'] ?? '';
          $lineTotal  = $line['line_total'] ?? '';
        @endphp
        <tr>
          <td>{{ $index + 1 }}</td>
          <td class="desc">
            {{ $desc }}
            @if(!empty($line['product_description']))
              <div style="font-size:11px; margin-top:3px;">{!! strip_tags($line['product_description']) !!}</div>
            @endif
          </td>
          <td>{{ $qty }}</td>
          <td class="money">{{ $unitPrice }}</td>
          <td class="money">{{ $lineTotal }}</td>
        </tr>
        @endforeach
      </tbody>

      <tfoot>
        {{-- Discount --}}
        @if(!empty($receipt_details->discount))
        <tr>
          <td colspan="4">{{ $receipt_details->discount_label ?? 'Discount' }}</td>
          <td>(-) {{ $receipt_details->discount }}</td>
        </tr>
        @endif

        {{-- Subtotal --}}
        @if(!empty($receipt_details->subtotal))
        <tr>
          <td colspan="4">Subtotal</td>
          <td>{{ $receipt_details->subtotal }}</td>
        </tr>
        @endif

        {{-- Bank Transfer (invoice only) --}}
        @if(!$isQuotation && !empty($bankTransferTotal))
        <tr>
          <td colspan="4">Bank Transfer</td>
          <td>{{ $bankTransferTotal }}</td>
        </tr>
        @endif

        {{-- Payments --}}
        @if(!empty($receipt_details->payments))
          @foreach($receipt_details->payments as $payment)
          <tr>
            <td colspan="4">{{ $payment['method'] }}</td>
            <td>{{ $payment['amount'] }}</td>
          </tr>
          @endforeach
        @endif

        {{-- Due --}}
        @if(!$isQuotation && !empty($receipt_details->total_due) && $receipt_details->total_due != '0.00' && $receipt_details->total_due != 0)
        <tr style="color:red;">
          <td colspan="4">Total Due</td>
          <td>{{ $receipt_details->total_due }}</td>
        </tr>
        @endif

        {{-- Grand Total --}}
        <tr class="grand-total">
          <td colspan="4">Total</td>
          <td>{{ $receipt_details->total ?? '' }}</td>
        </tr>
      </tfoot>
    </table>

    {{-- Payment mode / bank details from form --}}
    @if(!empty($receipt_details->preferred_payment_method) || !empty($receipt_details->pdf_bank_details) || !empty($receipt_details->preferred_account_details))
    <div class="payment-note">
      @if(!empty($receipt_details->preferred_payment_method))
        Payment mode: {{ $receipt_details->preferred_payment_method }}<br>
      @endif
      @if(!empty($receipt_details->pdf_bank_details))
        <br>{!! nl2br(e($receipt_details->pdf_bank_details)) !!}
      @elseif(!empty($receipt_details->preferred_account_details))
        @php $acc = (array)$receipt_details->preferred_account_details; @endphp
        <br>
        @if(!empty($acc['account_number'])) Account No: {{ $acc['account_number'] }}<br>@endif
        @if(!empty($acc['bank_name'])) Bank: {{ $acc['bank_name'] }}<br>@endif
        @if(!empty($acc['branch'])) Branch: {{ $acc['branch'] }}<br>@endif
        @if(!empty($acc['account_holder_name'])) Name: {{ $acc['account_holder_name'] }}@endif
      @endif
    </div>
    @endif

    {{-- System message --}}
    <div class="sys-msg">
      This is a system generated <strong>{{ strtolower($docTitle) }}</strong>.
    </div>

    {{-- Signature --}}
    <div class="sign-block">
      @if(!empty($signB64))
        <img src="data:image/png;base64,{{ $signB64 }}" alt="Signature">
      @endif
      Yours faithfully,<br>
      <strong>Sandaruwan Dharampriya</strong>,<br>
      Director<br>
      <em>Attract wear &amp; printing solutions</em>
    </div>

  </div>{{-- /.content-area --}}

</div>{{-- /.a4-sheet --}}

</body>
</html>
