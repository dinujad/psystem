<script type="text/javascript">
    $(document).ready(function () {
        if ($('#purchase_list_filter_date_range').length && $.fn.daterangepicker) {
            $('#purchase_list_filter_date_range').daterangepicker(
                dateRangeSettings,
                function (start, end) {
                    $('#purchase_list_filter_date_range').val(
                        start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                    );
                    purchase_table.ajax.reload();
                }
            );
            $('#purchase_list_filter_date_range').on('cancel.daterangepicker', function () {
                $('#purchase_list_filter_date_range').val('');
                purchase_table.ajax.reload();
            });
        }

        purchase_table = $('#purchase_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/accounting/transactions/purchases?type=purchase_payment',
                data: function (d) {
                    if ($('#purchase_list_filter_location_id').length) {
                        d.location_id = $('#purchase_list_filter_location_id').val();
                    }
                    if ($('#purchase_list_filter_supplier_id').length) {
                        d.supplier_id = $('#purchase_list_filter_supplier_id').val();
                    }
                    if ($('#purchase_list_filter_payment_status').length) {
                        d.payment_status = $('#purchase_list_filter_payment_status').val();
                    }
                    if ($('#purchase_list_filter_status').length) {
                        d.status = $('#purchase_list_filter_status').val();
                    }

                    var start = '';
                    var end = '';
                    if ($('#purchase_list_filter_date_range').val()) {
                        var picker = $('input#purchase_list_filter_date_range').data('daterangepicker');
                        if (picker) {
                            start = picker.startDate.format('YYYY-MM-DD');
                            end = picker.endDate.format('YYYY-MM-DD');
                        }
                    }
                    d.start_date = start;
                    d.end_date = end;

                    d = __datatable_ajax_callback(d);
                },
            },
            aaSorting: [[2, 'desc']],
            columns: [
                { data: 'mapping', name: 'mapping', orderable: false, searchable: false },
                { data: 'chart_of_account', name: 'chart_of_account', orderable: false, searchable: false },
                { data: 'transaction_date', name: 'transaction_date' },
                { data: 'ref_no', name: 'ref_no' },
                { data: 'location_name', name: 'BS.name' },
                { data: 'name', name: 'contacts.name' },
                { data: 'status', name: 'status' },
                { data: 'payment_status', name: 'payment_status' },
                { data: 'final_total', name: 'final_total' },
                { data: 'payment_due', name: 'payment_due', orderable: false, searchable: false },
                { data: 'added_by', name: 'u.first_name' },
            ],
            fnDrawCallback: function () {
                __currency_convert_recursively($('#purchase_table'));
            },
            footerCallback: function (row, data) {
                var total_purchase = 0;
                var total_due = 0;
                var total_purchase_return_due = 0;
                for (var r in data) {
                    total_purchase += $(data[r].final_total).data('orig-value')
                        ? parseFloat($(data[r].final_total).data('orig-value'))
                        : 0;
                    var payment_due_obj = $('<div>' + data[r].payment_due + '</div>');
                    total_due += payment_due_obj.find('.payment_due').data('orig-value')
                        ? parseFloat(payment_due_obj.find('.payment_due').data('orig-value'))
                        : 0;
                    total_purchase_return_due += payment_due_obj.find('.purchase_return').data('orig-value')
                        ? parseFloat(payment_due_obj.find('.purchase_return').data('orig-value'))
                        : 0;
                }

                $('.footer_purchase_total').html(__currency_trans_from_en(total_purchase));
                $('.footer_total_due').html(__currency_trans_from_en(total_due));
                $('.footer_total_purchase_return_due').html(__currency_trans_from_en(total_purchase_return_due));
                $('.footer_status_count').html(__count_status(data, 'status'));
                $('.footer_payment_status_count').html(__count_status(data, 'payment_status'));
            },
            createdRow: function (row) {
                $(row).find('td:eq(5)').attr('class', 'clickable_td');
            },
        });

        $(document).on(
            'change',
            '#purchase_list_filter_location_id, #purchase_list_filter_supplier_id, #purchase_list_filter_payment_status, #purchase_list_filter_status',
            function () {
                purchase_table.ajax.reload();
            }
        );
    });
</script>
