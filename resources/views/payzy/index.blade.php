@extends('layouts.app')
@section('title', 'Payzy BNPL')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Payzy — 4 Installment (BNPL)</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => 'Payzy BNPL payments'])
        <p class="text-muted" style="margin-bottom:12px;">
            Customer pays invoice + <strong>12% Payzy tax</strong> in 4 installments via Payzy.
            Settlements post to the <strong>Payzy BNPL</strong> payment account.
            Payzy has no payout/refund API — use <em>Mark refunded</em> here after you refund in the Payzy portal.
        </p>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="payzy_table" style="width:100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Invoice</th>
                        <th>Order ID</th>
                        <th>Base</th>
                        <th>Tax 12%</th>
                        <th>IPG total</th>
                        <th>Installments</th>
                        <th>Status</th>
                        <th>Paid on</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>

<div class="modal fade" id="payzy_refund_modal" tabindex="-1">
    <div class="modal-dialog">
        <form id="payzy_refund_form">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Mark Payzy refunded (manual)</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="payzy_refund_id">
                    <p>Payzy has no refund API. After you refund in the Payzy merchant portal, mark it here for ERP records.</p>
                    <div class="form-group">
                        <label>Note</label>
                        <textarea class="form-control" id="payzy_refund_note" rows="2" placeholder="Portal refund reference…"></textarea>
                    </div>
                    <p class="text-danger" id="payzy_refund_err"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Mark refunded</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('javascript')
<script>
$(function () {
    var table = $('#payzy_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('payzy.index') }}",
        order: [[0, 'desc']],
        columns: [
            { data: 'id', name: 'id' },
            { data: 'invoice', name: 'invoice_no', orderable: false },
            { data: 'order_id', name: 'order_id' },
            { data: 'base_amount', name: 'base_amount' },
            { data: 'tax_amount', name: 'tax_amount' },
            { data: 'charge_amount', name: 'charge_amount' },
            { data: 'installments', name: 'installments', orderable: false, searchable: false },
            { data: 'status', name: 'status' },
            { data: 'paid_on', name: 'paid_on' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $(document).on('click', '.payzy-refund-btn', function () {
        $('#payzy_refund_id').val($(this).data('id'));
        $('#payzy_refund_note').val('');
        $('#payzy_refund_err').text('');
        $('#payzy_refund_modal').modal('show');
    });

    $('#payzy_refund_form').on('submit', function (e) {
        e.preventDefault();
        var id = $('#payzy_refund_id').val();
        $.ajax({
            method: 'POST',
            url: "{{ url('payzy') }}/" + id + "/mark-refunded",
            data: { _token: '{{ csrf_token() }}', refund_note: $('#payzy_refund_note').val() },
            success: function (res) {
                if (res.success) {
                    $('#payzy_refund_modal').modal('hide');
                    toastr.success(res.msg);
                    table.ajax.reload();
                } else {
                    $('#payzy_refund_err').text(res.msg || 'Failed');
                }
            },
            error: function (xhr) {
                $('#payzy_refund_err').text((xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'Failed');
            }
        });
    });
});
</script>
@endsection
