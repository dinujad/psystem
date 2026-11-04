@extends('accounting::transactions.sales.layout')
@section('sub-tab-title')
    {{ trans_choice('accounting::general.payment', 2) }}
@endsection

@section('sub-tab-content')
    <!-- Main content -->
    <section class="content no-print">

        @component('components.widget', ['class' => 'box-primary', 'title' => ''])
            @if (auth()->user()->can('sell.view') ||
    auth()->user()->can('direct_sell.view') ||
    auth()->user()->can('view_own_sell_only') ||
    auth()->user()->can('view_commission_agent_sell'))
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="sell_table">
                        <thead>
                            <tr>
                                <th>@lang('accounting::general.mapping')</th>
                                <th>{{ trans_choice('accounting::general.chart_of_account', 1) }}</th>
                                <th>@lang('messages.date')</th>
                                <th>@lang('sale.invoice_no')</th>
                                <th>@lang('sale.customer_name')</th>
                                <th>@lang('sale.location')</th>
                                <th>@lang('sale.payment_status')</th>
                                <th>@lang('lang_v1.payment_method')</th>
                                <th>@lang('sale.total_amount')</th>
                                <th>@lang('sale.total_paid')</th>
                                <th>@lang('lang_v1.sell_due')</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr class="bg-gray font-17 footer-total text-center">
                                <td colspan="8"><strong>@lang('sale.total'):</strong></td>
                                <td class="footer_sale_total"></td>
                                <td class="footer_total_paid"></td>
                                <td class="footer_total_remaining"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        @endcomponent
    </section>
    <!-- /.content -->
    <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
@endsection

@section('sub-tab-javascript')
    @include('accounting::transactions.js.payment_js')
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
