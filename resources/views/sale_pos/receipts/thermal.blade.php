{{-- Thermal-friendly single-column receipt - PrintWorks --}}
<style>
* { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #000; }
.rcpt { width: 100%; max-width: 400px; margin: 0 auto; padding: 4px; }
.rcpt-center { text-align: center; }
.rcpt-bold { font-weight: bold; }
.rcpt-line { border-top: 1px dashed #000; margin: 6px 0; }
.rcpt-line-solid { border-top: 1px solid #000; margin: 6px 0; }
.rcpt-brand { text-align: center; margin: 6px 0 8px; padding: 4px 0; }
.rcpt-shop-name { font-size: 16px; font-weight: bold; text-align: center; margin: 0; line-height: 1.3; letter-spacing: 0.3px; }
.rcpt-tagline { font-size: 11px; text-align: center; margin: 4px 0 0; }
.rcpt-contact { font-size: 11px; text-align: center; margin: 1px 0; }
.rcpt-heading { font-size: 15px; font-weight: bold; text-align: center; margin: 4px 0 2px; letter-spacing: 1px; }
.rcpt-info-row { display: flex; justify-content: space-between; margin: 1px 0; }
.rcpt-info-row span { font-size: 11px; }
.rcpt-customer { font-size: 11px; margin: 2px 0; }
.rcpt-table { width: 100%; border-collapse: collapse; margin: 4px 0; table-layout: fixed; }
.rcpt-table th { font-size: 11px; font-weight: bold; border-bottom: 1px solid #000; padding: 3px 2px; text-align: left; line-height: 1.2; }
.rcpt-table th.r, .rcpt-table td.r { text-align: right; }
.rcpt-table td { font-size: 11px; padding: 3px 2px; vertical-align: top; }
.rcpt-table td.r { white-space: nowrap; }
.rcpt-table tbody tr { border-bottom: 1px dashed #ccc; }
.rcpt-totals { width: 100%; margin: 4px 0; }
.rcpt-totals tr td { font-size: 11px; padding: 2px 2px; }
.rcpt-totals tr td:first-child { text-align: left; }
.rcpt-totals tr td:last-child { text-align: right; }
.rcpt-totals .grand-total td { font-size: 13px; font-weight: bold; border-top: 1px solid #000; padding-top: 4px; }
.rcpt-payments { width: 100%; margin: 2px 0; }
.rcpt-payments tr td { font-size: 11px; padding: 2px 2px; }
.rcpt-payments tr td:last-child { text-align: right; }
.rcpt-footer-sys { font-size: 10px; text-align: center; margin-top: 8px; color: #444; }
@media print {
    * { font-family: Arial, Helvetica, sans-serif !important; font-size: 12px !important; color: #000 !important; }
    .rcpt { width: 100%; max-width: 100%; }
}
</style>

<div class="rcpt">

    {{-- ===== HEADER ===== --}}
    @if(empty($receipt_details->letter_head))
        <div class="rcpt-brand">
            <p class="rcpt-shop-name">{{ !empty($receipt_details->display_name) ? $receipt_details->display_name : config('app.name', 'PrintWorks') }}</p>
            @if(!empty($receipt_details->sub_heading_line1))
            <p class="rcpt-tagline">{{ $receipt_details->sub_heading_line1 }}</p>
            @endif
        </div>

        @if(!empty($receipt_details->address))
            <p class="rcpt-contact">Address: {!! $receipt_details->address !!}</p>
        @endif
        @if(!empty($receipt_details->contact))
            <p class="rcpt-contact">{!! $receipt_details->contact !!}</p>
        @endif
        @if(!empty($receipt_details->website))
            <p class="rcpt-contact">{{ $receipt_details->website }}</p>
        @endif
        @if(!empty($receipt_details->location_custom_fields))
            <p class="rcpt-contact">{{ $receipt_details->location_custom_fields }}</p>
        @endif

        @if(!empty($receipt_details->header_text))
            <p class="rcpt-center" style="font-size:11px; margin:2px 0;">{!! $receipt_details->header_text !!}</p>
        @endif

        @if(!empty($receipt_details->sub_heading_line1) || !empty($receipt_details->sub_heading_line2) || !empty($receipt_details->sub_heading_line3))
            <p class="rcpt-center" style="font-size:11px; margin:2px 0;">
                @if(!empty($receipt_details->sub_heading_line1)) {{ $receipt_details->sub_heading_line1 }}<br> @endif
                @if(!empty($receipt_details->sub_heading_line2)) {{ $receipt_details->sub_heading_line2 }}<br> @endif
                @if(!empty($receipt_details->sub_heading_line3)) {{ $receipt_details->sub_heading_line3 }}<br> @endif
                @if(!empty($receipt_details->sub_heading_line4)) {{ $receipt_details->sub_heading_line4 }}<br> @endif
                @if(!empty($receipt_details->sub_heading_line5)) {{ $receipt_details->sub_heading_line5 }}<br> @endif
            </p>
        @endif

        @if(!empty($receipt_details->tax_info1))
            <p class="rcpt-center" style="font-size:11px; margin:1px 0;">
                <b>{{ $receipt_details->tax_label1 }}</b> {{ $receipt_details->tax_info1 }}
                @if(!empty($receipt_details->tax_info2)) &nbsp; <b>{{ $receipt_details->tax_label2 }}</b> {{ $receipt_details->tax_info2 }} @endif
            </p>
        @endif
    @else
        <img style="width:100%; margin-bottom:6px;" src="{{ $receipt_details->letter_head }}" alt="">
    @endif

    <div class="rcpt-line-solid"></div>
    <p class="rcpt-heading">{!! !empty($receipt_details->invoice_heading) ? $receipt_details->invoice_heading : 'Invoice' !!}</p>
    <div class="rcpt-line-solid"></div>

    {{-- ===== INVOICE INFO ===== --}}
    <div class="rcpt-info-row">
        <span>
            @if(!empty($receipt_details->invoice_no_prefix))
                <b>{!! $receipt_details->invoice_no_prefix !!}</b>
            @endif
            {{ $receipt_details->invoice_no }}
        </span>
        <span>{{ $receipt_details->invoice_date }}</span>
    </div>

    @if(!empty($receipt_details->due_date_label))
        <div class="rcpt-info-row">
            <span><b>{{ $receipt_details->due_date_label }}</b></span>
            <span>{{ $receipt_details->due_date ?? '' }}</span>
        </div>
    @endif

    @php
        $customer_display = '';
        if (!empty($receipt_details->customer_name)) {
            $customer_display = $receipt_details->customer_name;
        } elseif (!empty($receipt_details->customer_info)) {
            $customer_display = trim(preg_replace('/\s+/', ' ', strip_tags($receipt_details->customer_info)));
        }
    @endphp
    @if(!empty($customer_display))
        <p class="rcpt-customer"><b>{{ $receipt_details->customer_label ?? 'Customer' }}:</b> {{ $customer_display }}</p>
    @endif

    @if(!empty($receipt_details->sales_person_label))
        <div class="rcpt-info-row">
            <span><b>{{ $receipt_details->sales_person_label }}</b></span>
            <span>{{ $receipt_details->sales_person }}</span>
        </div>
    @endif

    @if(!empty($receipt_details->types_of_service))
        <div class="rcpt-info-row">
            <span><b>{!! $receipt_details->types_of_service_label !!}</b></span>
            <span>{{ $receipt_details->types_of_service }}</span>
        </div>
    @endif

    @if(!empty($receipt_details->table_label) || !empty($receipt_details->table))
        <div class="rcpt-info-row">
            <span><b>{!! $receipt_details->table_label !!}</b></span>
            <span>{{ $receipt_details->table }}</span>
        </div>
    @endif

    @if(!empty($receipt_details->sell_custom_field_1_value))
        <div class="rcpt-info-row">
            <span><b>{{ $receipt_details->sell_custom_field_1_label }}</b></span>
            <span>{{ $receipt_details->sell_custom_field_1_value }}</span>
        </div>
    @endif
    @if(!empty($receipt_details->sell_custom_field_2_value))
        <div class="rcpt-info-row">
            <span><b>{{ $receipt_details->sell_custom_field_2_label }}</b></span>
            <span>{{ $receipt_details->sell_custom_field_2_value }}</span>
        </div>
    @endif

    @includeIf('sale_pos.receipts.partial.common_repair_invoice')

    {{-- ===== ITEMS TABLE ===== --}}
    <div class="rcpt-line"></div>
    @php
        $showDiscount = !empty($receipt_details->item_discount_label);
        $showDiscountedPrice = !empty($receipt_details->discounted_unit_price_label);
        $formatQty = function ($qty) {
            if ($qty === null || $qty === '') {
                return '';
            }
            if (is_numeric($qty)) {
                $formatted = rtrim(rtrim(number_format((float) $qty, 2, '.', ''), '0'), '.');

                return $formatted === '' ? '0' : $formatted;
            }

            return $qty;
        };
    @endphp
    <table class="rcpt-table">
        <thead>
            <tr>
                <th style="width:44%;">{{ $receipt_details->table_product_label }}</th>
                <th class="r" style="width:12%;">Qty</th>
                <th class="r" style="width:20%;">Unit<br>Price</th>
                @if($showDiscount)
                    <th class="r" style="width:8%;">{{ $receipt_details->item_discount_label }}</th>
                @endif
                <th class="r" style="width:16%;">Sub<br>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($receipt_details->lines as $line)
                <tr>
                    <td>
                        {{ $line['name'] }}
                        @if(!empty($line['product_variation'])) {{ $line['product_variation'] }} @endif
                        @if(!empty($line['variation'])) {{ $line['variation'] }} @endif
                        @if(!empty($line['sub_sku'])) <br><small>{{ $line['sub_sku'] }}</small> @endif
                        @if(!empty($line['sell_line_note'])) <br><small>{!! $line['sell_line_note'] !!}</small> @endif
                        @if(!empty($line['lot_number'])) <br><small>{{ $line['lot_number_label'] }}: {{ $line['lot_number'] }}</small> @endif
                        @if(!empty($line['product_expiry'])) <br><small>{{ $line['product_expiry_label'] }}: {{ $line['product_expiry'] }}</small> @endif
                        @if(!empty($line['warranty_name'])) <br><small>{{ $line['warranty_name'] }}@if(!empty($line['warranty_exp_date'])) - {{ format_date($line['warranty_exp_date']) }}@endif</small> @endif
                    </td>
                    <td class="r">{{ $formatQty($line['quantity']) }}</td>
                    <td class="r">{{ $line['unit_price_before_discount'] }}</td>
                    @if($showDiscount)
                        <td class="r">{{ $line['total_line_discount'] ?? '0.00' }}</td>
                    @endif
                    <td class="r">{{ $line['line_total'] }}</td>
                </tr>
                @if(!empty($line['modifiers']))
                    @foreach($line['modifiers'] as $modifier)
                        <tr>
                            <td>&nbsp;&nbsp;+ {{ $modifier['name'] }} {{ $modifier['variation'] }}</td>
                            <td class="r">{{ $formatQty($modifier['quantity']) }}</td>
                            <td class="r">{{ $modifier['unit_price_inc_tax'] }}</td>
                            @if($showDiscount) <td class="r">-</td> @endif
                            <td class="r">{{ $modifier['line_total'] }}</td>
                        </tr>
                    @endforeach
                @endif
            @empty
                <tr><td colspan="4">&nbsp;</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ===== TOTALS ===== --}}
    <div class="rcpt-line"></div>
    <table class="rcpt-totals">
        <tbody>
            @if(!empty($receipt_details->total_quantity_label))
                <tr><td>{!! $receipt_details->total_quantity_label !!}</td><td>{{ $receipt_details->total_quantity }}</td></tr>
            @endif
            @if(!empty($receipt_details->total_items_label))
                <tr><td>{!! $receipt_details->total_items_label !!}</td><td>{{ $receipt_details->total_items }}</td></tr>
            @endif
            <tr><td>{!! $receipt_details->subtotal_label !!}</td><td>{{ $receipt_details->subtotal }}</td></tr>

            @if(!empty($receipt_details->shipping_charges))
                <tr><td>{!! $receipt_details->shipping_charges_label !!}</td><td>{{ $receipt_details->shipping_charges }}</td></tr>
            @endif
            @if(!empty($receipt_details->packing_charge))
                <tr><td>{!! $receipt_details->packing_charge_label !!}</td><td>{{ $receipt_details->packing_charge }}</td></tr>
            @endif
            @if(!empty($receipt_details->discount))
                <tr><td>{!! $receipt_details->discount_label !!}</td><td>(-) {{ $receipt_details->discount }}</td></tr>
            @endif
            @if(!empty($receipt_details->total_line_discount))
                <tr><td>{!! $receipt_details->line_discount_label !!}</td><td>(-) {{ $receipt_details->total_line_discount }}</td></tr>
            @endif
            @if(!empty($receipt_details->additional_expenses))
                @foreach($receipt_details->additional_expenses as $key => $val)
                    <tr><td>{{ $key }}</td><td>(+) {{ $val }}</td></tr>
                @endforeach
            @endif
            @if(!empty($receipt_details->reward_point_label))
                <tr><td>{!! $receipt_details->reward_point_label !!}</td><td>(-) {{ $receipt_details->reward_point_amount }}</td></tr>
            @endif
            @if(!empty($receipt_details->tax))
                <tr><td>{!! $receipt_details->tax_label !!}</td><td>(+) {{ $receipt_details->tax }}</td></tr>
            @endif
            @if($receipt_details->round_off_amount > 0)
                <tr><td>{!! $receipt_details->round_off_label !!}</td><td>{{ $receipt_details->round_off }}</td></tr>
            @endif
            <tr class="grand-total">
                <td><b>{!! $receipt_details->total_label !!}</b></td>
                <td><b>{{ $receipt_details->total }}</b></td>
            </tr>
            @if(!empty($receipt_details->total_in_words))
                <tr><td colspan="2" style="font-size:10px; text-align:center;">({{ $receipt_details->total_in_words }})</td></tr>
            @endif
        </tbody>
    </table>

    {{-- ===== PAYMENTS ===== --}}
    @if(!empty($receipt_details->payments) || !empty($receipt_details->total_paid))
        <div class="rcpt-line"></div>
        <table class="rcpt-payments">
            @if(!empty($receipt_details->payments))
                @foreach($receipt_details->payments as $payment)
                    <tr>
                        <td>{{ $payment['method'] }}</td>
                        <td>{{ $payment['amount'] }}</td>
                    </tr>
                @endforeach
            @endif
            @if(!empty($receipt_details->total_paid))
                <tr>
                    <td><b>{!! $receipt_details->total_paid_label !!}</b></td>
                    <td><b>{{ $receipt_details->total_paid }}</b></td>
                </tr>
            @endif
            @if(!empty($receipt_details->total_due) && !empty($receipt_details->total_due_label))
                <tr>
                    <td><b>{!! $receipt_details->total_due_label !!}</b></td>
                    <td><b>{{ $receipt_details->total_due }}</b></td>
                </tr>
            @endif
            @if(!empty($receipt_details->total_previous_due))
                <tr>
                    <td>{!! $receipt_details->total_previous_due_label !!}</td>
                    <td>{{ $receipt_details->total_previous_due }}</td>
                </tr>
            @endif
            @if(!empty($receipt_details->all_due))
                <tr>
                    <td>{!! $receipt_details->all_bal_label !!}</td>
                    <td>{{ $receipt_details->all_due }}</td>
                </tr>
            @endif
        </table>
    @endif

    {{-- ===== TAX SUMMARY ===== --}}
    @if(!empty($receipt_details->taxes) && !empty($receipt_details->tax_summary_label))
        <div class="rcpt-line"></div>
        <p class="rcpt-center" style="font-size:11px; margin:2px 0;"><b>{{ $receipt_details->tax_summary_label }}</b></p>
        <table class="rcpt-table">
            @foreach($receipt_details->taxes as $key => $val)
                <tr><td>{{ $key }}</td><td class="r">{{ $val }}</td></tr>
            @endforeach
        </table>
    @endif

    {{-- ===== NOTES ===== --}}
    @if(!empty($receipt_details->additional_notes))
        <div class="rcpt-line"></div>
        <p style="font-size:11px; text-align:center;">{!! nl2br($receipt_details->additional_notes) !!}</p>
    @endif

    {{-- ===== FOOTER TEXT ===== --}}
    @if(!empty($receipt_details->footer_text))
        <div class="rcpt-line"></div>
        <p style="font-size:11px; text-align:center;">{!! $receipt_details->footer_text !!}</p>
    @endif

    {{-- ===== BARCODE / QR ===== --}}
    @if($receipt_details->show_barcode || $receipt_details->show_qr_code)
        <div class="rcpt-line"></div>
        @if($receipt_details->show_barcode)
            <img class="center-block" style="display:block;margin:0 auto;" src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2, 30, [39,48,54], true)}}">
        @endif
        @if($receipt_details->show_qr_code && !empty($receipt_details->qr_code_text))
            <img class="center-block" style="display:block;margin:4px auto 0;" src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_text, 'QRCODE', 3, 3, [39,48,54])}}">
        @endif
    @endif

    <div class="rcpt-line"></div>
    <p class="rcpt-footer-sys">System by PrintWorks</p>

</div>
