@extends('layouts.app')
@section('title', 'OnePay Payments')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">OnePay Payments</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => 'Online invoice payments via OnePay'])
        <p class="text-muted" style="margin-bottom:12px;">
            Successful OnePay invoice payments post to the <strong>OnePay Payouts</strong> payment account.
            Use Refund to send a full or partial refund through OnePay.
        </p>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="onepay_table" style="width:100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Invoice</th>
                        <th>Reference</th>
                        <th>OnePay ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Paid on</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>

<div class="modal fade" id="onepay_refund_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form id="onepay_refund_form">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Refund OnePay payment</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="refund_payment_id">
                    <div class="form-group">
                        <label>Refund type</label>
                        <select class="form-control" id="refund_partial" name="is_partially">
                            <option value="0">Full refund</option>
                            <option value="1">Partial refund</option>
                        </select>
                    </div>
                    <div class="form-group" id="refund_amount_wrap" style="display:none;">
                        <label>Amount</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="refund_amount" name="amount">
                    </div>
                    <div class="form-group">
                        <label>Reason</label>
                        <select class="form-control" name="refund_reason" id="refund_reason" required>
                            <option value="REQUESTED_BY_CUSTOMER">Requested by customer</option>
                            <option value="DUPLICATED">Duplicated</option>
                            <option value="FRAUDULENT">Fraudulent</option>
                            <option value="OUT_OF_ORDER">Out of order</option>
                            <option value="OTHER">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Note</label>
                        <textarea class="form-control" name="refund_note" id="refund_note" rows="2"></textarea>
                    </div>
                    <p class="text-danger" id="refund_err"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Submit refund</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('javascript')
<script>
$(function () {
    var table = $('#onepay_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('onepay.index') }}",
        order: [[0, 'desc']],
        columns: [
            { data: 'id', name: 'id' },
            { data: 'invoice', name: 'invoice_no', orderable: false },
            { data: 'reference', name: 'reference' },
            { data: 'ipg_transaction_id', name: 'ipg_transaction_id' },
            { data: 'customer_name', name: 'customer_name' },
            { data: 'amount', name: 'amount' },
            { data: 'status', name: 'status' },
            { data: 'paid_on', name: 'paid_on' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $(document).on('click', '.onepay-refund-btn', function () {
        $('#refund_payment_id').val($(this).data('id'));
        $('#refund_amount').val($(this).data('amount'));
        $('#refund_partial').val('0');
        $('#refund_amount_wrap').hide();
        $('#refund_err').text('');
        $('#onepay_refund_modal').modal('show');
    });

    $('#refund_partial').on('change', function () {
        $('#refund_amount_wrap').toggle($(this).val() === '1');
    });

    $('#onepay_refund_form').on('submit', function (e) {
        e.preventDefault();
        var id = $('#refund_payment_id').val();
        $('#refund_err').text('');
        $.ajax({
            method: 'POST',
            url: "{{ url('onepay') }}/" + id + "/refund",
            data: {
                _token: '{{ csrf_token() }}',
                is_partially: $('#refund_partial').val() === '1' ? 1 : 0,
                amount: $('#refund_amount').val(),
                refund_reason: $('#refund_reason').val(),
                refund_note: $('#refund_note').val()
            },
            success: function (res) {
                if (res.success) {
                    $('#onepay_refund_modal').modal('hide');
                    toastr.success(res.msg);
                    table.ajax.reload();
                } else {
                    $('#refund_err').text(res.msg || 'Refund failed');
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'Refund failed';
                $('#refund_err').text(msg);
            }
        });
    });
});
</script>
@endsection
