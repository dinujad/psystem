@extends('accounting::layouts.transactions_layout')
@section('tab-title')
    {{ trans_choice('accounting::general.expense', 2) }}
@endsection

@section('modal-content')
    @include('accounting::transactions.partials.map_transactions_modal', [
        'map_type' => 'debit',
        'mapping_for' => 'expense',
    ])
@endsection

@section('tab-content')
    @include('accounting::transactions.partials.map_all_bar')
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    <div class="row">
                        @if (auth()->user()->can('all_expense.access'))
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                                </div>
                            </div>

                            <div class="col-sm-3">
                                <div class="form-group">
                                    {!! Form::label('expense_for', __('expense.expense_for') . ':') !!}
                                    {!! Form::select('expense_for', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                                </div>
                            </div>
                        @endif
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('expense_category_id', __('expense.expense_category') . ':') !!}
                                {!! Form::select('expense_category_id', $categories, null, ['placeholder' => __('report.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'expense_category_id']) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('expense_date_range', __('report.date_range') . ':') !!}
                                {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'expense_date_range', 'readonly']) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('expense_payment_status', __('purchase.payment_status') . ':') !!}
                                {!! Form::select('expense_payment_status', ['paid' => __('lang_v1.paid'), 'due' => __('lang_v1.due'), 'partial' => __('lang_v1.partial')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
                            </div>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => ''])
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="expense_transactions_table">
                            <thead>
                                <tr>
                                    <th>@lang('accounting::general.mapping')</th>
                                    <th>{{ trans_choice('accounting::general.chart_of_account', 1) }}</th>
                                    <th>@lang('messages.date')</th>
                                    <th>@lang('purchase.ref_no')</th>
                                    <th>@lang('lang_v1.recur_details')</th>
                                    <th>@lang('expense.expense_category')</th>
                                    <th>@lang('business.location')</th>
                                    <th>@lang('sale.payment_status')</th>
                                    <th class="d-none">@lang('product.tax')</th>
                                    <th>@lang('sale.total_amount')</th>
                                    <th>@lang('purchase.payment_due')</th>
                                    <th>@lang('expense.expense_for')</th>
                                    <th>@lang('contact.contact')</th>
                                    <th>@lang('expense.expense_note')</th>
                                    <th>@lang('lang_v1.added_by')</th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr class="bg-gray font-17 text-center footer-total">
                                    <td colspan="7"><strong>@lang('sale.total'):</strong></td>
                                    <td class="footer_payment_status_count"></td>
                                    <td></td>
                                    <td class="footer_expense_total"></td>
                                    <td class="footer_total_due"></td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endcomponent
            </div>
        </div>

    </section>
    <!-- /.content -->
    <!-- /.content -->
    <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
@endsection

@section('tab-javascript')
    {{-- For the mapping_modal --}}
    <script src="{{ Module::asset('accounting:js/accounting_transactions.js') }}"></script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            if ($('#expense_date_range').length && $.fn.daterangepicker) {
                $('#expense_date_range').daterangepicker(
                    $.extend({}, dateRangeSettings, {autoUpdateInput: false}),
                    function(start, end) {
                        $('#expense_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                        expense_transactions_table.ajax.reload();
                    }
                );
                $('#expense_date_range').on('cancel.daterangepicker', function() {
                    $('#expense_date_range').val('');
                    expense_transactions_table.ajax.reload();
                });
            }

            expense_transactions_table = $('#expense_transactions_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [[2, 'desc']],
                ajax: {
                    url: '/accounting/transactions/expenses',
                    data: function(d) {
                        d.expense_for = $('select#expense_for').val();
                        d.location_id = $('select#location_id').val();
                        d.expense_category_id = $('select#expense_category_id').val();
                        d.payment_status = $('select#expense_payment_status').val();
                        var range = $('#expense_date_range').val();
                        var picker = $('#expense_date_range').data('daterangepicker');
                        if (range && picker) {
                            d.start_date = picker.startDate.format('YYYY-MM-DD');
                            d.end_date = picker.endDate.format('YYYY-MM-DD');
                        }
                    }
                },
                columns: [
                    { data: 'mapping', name: 'mapping', orderable: false, searchable: false },
                    { data: 'chart_of_account', name: 'chart_of_account', orderable: false, searchable: false },
                    { data: 'transaction_date', name: 'transactions.transaction_date' },
                    { data: 'ref_no', name: 'ref_no' },
                    { data: 'recur_details', name: 'recur_details', orderable: false, searchable: false },
                    { data: 'category', name: 'ec.name' },
                    { data: 'location_name', name: 'bl.name' },
                    { data: 'payment_status', name: 'payment_status', orderable: false },
                    { data: 'tax', name: 'tr.name', visible: false },
                    { data: 'final_total', name: 'final_total' },
                    { data: 'payment_due', name: 'payment_due', orderable: false, searchable: false },
                    { data: 'expense_for', name: 'expense_for', orderable: false, searchable: false },
                    { data: 'contact_name', name: 'c.name', visible: false },
                    { data: 'additional_notes', name: 'additional_notes' },
                    { data: 'added_by', name: 'usr.first_name' }
                ],
                fnDrawCallback: function() {
                    __currency_convert_recursively($('#expense_transactions_table'));
                }
            });

            $('select#location_id, select#expense_for, select#expense_category_id, select#expense_payment_status').on('change', function() {
                expense_transactions_table.ajax.reload();
            });
        });
    </script>
@endsection
