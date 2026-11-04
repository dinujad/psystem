<script type="text/javascript">
    $(document).ready(function() {
        if ($('#sell_list_filter_date_range').length && $.fn.daterangepicker) {
            $('#sell_list_filter_date_range').daterangepicker(
                $.extend({}, dateRangeSettings, { autoUpdateInput: false }),
                function(start, end) {
                    $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                    sell_table.ajax.reload();
                }
            );
            $('#sell_list_filter_date_range').on('cancel.daterangepicker', function() {
                $('#sell_list_filter_date_range').val('');
                sell_table.ajax.reload();
            });
        }

        sell_table = $('#sell_table').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 25,
            aaSorting: [[2, 'desc']],
            ajax: {
                url: "/accounting/transactions/sales?type={{ request()->type }}",
                data: function(d) {
                    var range = $('#sell_list_filter_date_range').val();
                    var picker = $('#sell_list_filter_date_range').data('daterangepicker');
                    if (range && picker) {
                        d.start_date = picker.startDate.format('YYYY-MM-DD');
                        d.end_date = picker.endDate.format('YYYY-MM-DD');
                    }
                    d.location_id = $('#sell_list_filter_location_id').val();
                    d.customer_id = $('#sell_list_filter_customer_id').val();
                    d.payment_status = $('#sell_list_filter_payment_status').val();
                    d.created_by = $('#created_by').val();
                    d.type = '{{ request()->type }}';
                    d = __datatable_ajax_callback(d);
                },
                error: function(xhr) {
                    if ($.fn.dataTable.isDataTable('#sell_table')) {
                        $('#sell_table').DataTable().processing(false);
                    }
                    var message = 'Could not load sales for mapping.';
                    try {
                        var body = xhr.responseJSON || JSON.parse(xhr.responseText);
                        if (body && body.error) {
                            message = body.error;
                        }
                    } catch (e) {}
                    if (typeof toastr !== 'undefined') {
                        toastr.error(message);
                    }
                }
            },
            columns: [
                { data: 'mapping', name: 'mapping', orderable: false, searchable: false },
                { data: 'chart_of_account', name: 'chart_of_account', orderable: false, searchable: false },
                { data: 'transaction_date', name: 'transactions.transaction_date' },
                { data: 'invoice_no', name: 'transactions.invoice_no' },
                { data: 'contact_name', name: 'contact_name' },
                { data: 'business_location', name: 'bl.name' },
                { data: 'payment_status', name: 'transactions.payment_status' },
                { data: 'payment_methods', name: 'payment_methods', orderable: false, searchable: false },
                { data: 'final_total', name: 'transactions.final_total' },
                { data: 'total_paid', name: 'total_paid', orderable: false, searchable: false },
                { data: 'total_remaining', name: 'total_remaining', orderable: false, searchable: false }
            ],
            fnDrawCallback: function() {
                __currency_convert_recursively($('#sell_table'));
            },
            footerCallback: function(row, data) {
                var footer_sale_total = 0;
                var footer_total_paid = 0;
                var footer_total_remaining = 0;
                for (var r = 0; r < data.length; r++) {
                    footer_sale_total += parseFloat($(data[r].final_total).data('orig-value')) || 0;
                    footer_total_paid += parseFloat($(data[r].total_paid).data('orig-value')) || 0;
                    footer_total_remaining += parseFloat($(data[r].total_remaining).data('orig-value')) || 0;
                }
                $('.footer_total_remaining').html(__currency_trans_from_en(footer_total_remaining));
                $('.footer_total_paid').html(__currency_trans_from_en(footer_total_paid));
                $('.footer_sale_total').html(__currency_trans_from_en(footer_sale_total));
            }
        });

        $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status, #created_by', function() {
            sell_table.ajax.reload();
        });
    });
</script>
