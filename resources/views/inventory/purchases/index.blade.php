@extends('layouts.app')
@section('title', 'Raw Material Purchases')

@section('css')
<style>
.rmp-page { padding: 0 20px 40px; }
.rmp-head { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin:8px 0 14px; }
.rmp-head h1 { margin:0; font-size:20px; font-weight:800; color:#111827; }
.rmp-btn { background:#0ea5e9; color:#fff; border:none; border-radius:9px; padding:9px 16px; font-size:13px; font-weight:700; text-decoration:none; display:inline-flex; cursor:pointer; }
.rmp-btn:hover { color:#fff; opacity:.92; text-decoration:none; }
.rmp-btn-secondary { background:#f3f4f6; color:#374151; }
.rmp-note { background:#ecfeff; border:1px solid #a5f3fc; color:#155e75; border-radius:10px; padding:10px 14px; font-size:13px; margin-bottom:14px; }
.rmp-badge { font-size:11px; font-weight:800; padding:3px 10px; border-radius:20px; text-transform:uppercase; }
.rmp-badge.received, .rmp-badge.pay-paid { background:#dcfce7; color:#15803d; }
.rmp-badge.ordered, .rmp-badge.pay-partial { background:#fef3c7; color:#b45309; }
.rmp-badge.pay-due { background:#fee2e2; color:#b91c1c; }
.rmp-modal-ov { position:fixed; inset:0; background:rgba(17,24,39,.5); z-index:100000; display:none; }
.rmp-modal-ov.show { display:block !important; }
.rmp-modal { background:#fff; border-radius:16px; width:calc(100% - 32px); max-width:480px; padding:18px 20px; color:#111827; position:fixed; left:50%; top:50%; transform:translate(-50%, -50%); margin:0; z-index:100001; }
.rmp-modal h3 { margin:0 0 8px; font-size:17px; font-weight:800; }
.rmp-field { margin-bottom:10px; }
.rmp-field label { display:block; font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; margin-bottom:4px; }
.rmp-field input, .rmp-field select { width:100%; border:1px solid #d1d5db; border-radius:8px; padding:8px 10px; font-size:13px; }
.rmp-err { color:#b91c1c; font-size:12px; font-weight:700; min-height:16px; }
body #scrollable-container .rmp-page input,
body #scrollable-container .rmp-page select,
body .rmp-modal input,
body .rmp-modal select,
body #scrollable-container .rmp-page select option {
    color-scheme: light !important;
    background: #fff !important;
    background-color: #fff !important;
    color: #111827 !important;
    -webkit-text-fill-color: #111827 !important;
}
</style>
@endsection

@section('content')
<div class="rmp-page">
    <div class="rmp-head">
        <div>
            <h1>Raw Material Purchases</h1>
            <div style="font-size:13px;color:#6b7280;margin-top:4px;">Every material on a purchase is listed here. Search, then see what is paid and what is still due.</div>
        </div>
        <a href="{{ route('inventory.purchases.create') }}" class="rmp-btn">+ Purchase Raw Materials</a>
    </div>

    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-3">
            <div class="form-group">
                <label for="rmp_supplier">Supplier</label>
                <select id="rmp_supplier" class="form-control">
                    <option value="">All</option>
                    @foreach($suppliers as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="rmp_status">Purchase status</label>
                <select id="rmp_status" class="form-control">
                    <option value="">All</option>
                    <option value="received">Received</option>
                    <option value="ordered">Ordered</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="rmp_pay_status">Payment status</label>
                <select id="rmp_pay_status" class="form-control">
                    <option value="">All</option>
                    <option value="paid">Paid</option>
                    <option value="partial">Partial</option>
                    <option value="due">Due</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="rmp_date_range">Date range</label>
                <input type="text" id="rmp_date_range" class="form-control" placeholder="Select a date range" readonly>
            </div>
        </div>
    @endcomponent

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @component('components.widget', ['class' => 'box-primary', 'title' => 'All raw material purchases'])
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="rmp_table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Supplier</th>
                        <th>Materials</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Due</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</div>

<div class="rmp-modal-ov" id="rmpPayModal">
    <div class="rmp-modal">
        <h3>Add payment</h3>
        <p id="rmpPayRef" style="font-size:13px;color:#6b7280;"></p>
        <div class="rmp-field">
            <label>Amount</label>
            <input type="number" id="rmpPayAmount" min="0.01" step="0.01">
        </div>
        <div class="rmp-field">
            <label>Date</label>
            <input type="date" id="rmpPayDate" value="{{ date('Y-m-d') }}">
        </div>
        <div class="rmp-field">
            <label>Method</label>
            <select id="rmpPayMethod">
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="bank">Bank transfer</option>
                <option value="cheque">Cheque</option>
            </select>
        </div>
        <div class="rmp-field">
            <label>Note</label>
            <input type="text" id="rmpPayNote" placeholder="Optional">
        </div>
        <p class="rmp-err" id="rmpPayErr"></p>
        <div style="display:flex;gap:8px;margin-top:8px;">
            <button type="button" class="rmp-btn" id="rmpPaySave">Save payment</button>
            <button type="button" class="rmp-btn rmp-btn-secondary" onclick="document.getElementById('rmpPayModal').classList.remove('show')">Cancel</button>
        </div>
    </div>
</div>

<div class="rmp-modal-ov" id="rmpPayListModal">
    <div class="rmp-modal" style="max-width:560px;">
        <h3 id="rmpPayListTitle">Payments</h3>
        <div id="rmpPayListBody" style="font-size:13px;"></div>
        <div style="margin-top:12px;">
            <button type="button" class="rmp-btn rmp-btn-secondary" onclick="document.getElementById('rmpPayListModal').classList.remove('show')">Close</button>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
var rmpTable;
var rmpPayId = null;

$(document).ready(function () {
    $('#rmp_date_range').daterangepicker(dateRangeSettings, function (start, end) {
        $('#rmp_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
        rmpTable.ajax.reload();
    });
    $('#rmp_date_range').on('cancel.daterangepicker', function () {
        $('#rmp_date_range').val('');
        rmpTable.ajax.reload();
    });

    rmpTable = $('#rmp_table').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        ajax: {
            url: "{{ route('inventory.purchases.index') }}",
            data: function (d) {
                d.supplier_id = $('#rmp_supplier').val();
                d.status = $('#rmp_status').val();
                d.payment_status = $('#rmp_pay_status').val();
                if ($('#rmp_date_range').val()) {
                    var picker = $('#rmp_date_range').data('daterangepicker');
                    d.start_date = picker.startDate.format('YYYY-MM-DD');
                    d.end_date = picker.endDate.format('YYYY-MM-DD');
                }
            }
        },
        columns: [
            { data: 'purchase_date', name: 'inventory_purchases.purchase_date' },
            { data: 'ref_no', name: 'inventory_purchases.ref_no' },
            { data: 'supplier_name', name: 'supplier_name', orderable: false },
            { data: 'material_names', name: 'material_names', orderable: false },
            { data: 'status', name: 'inventory_purchases.status' },
            { data: 'payment_status', name: 'inventory_purchases.payment_status' },
            { data: 'total_amount', name: 'inventory_purchases.total_amount' },
            { data: 'paid', name: 'amount_paid', orderable: false, searchable: false },
            { data: 'due', name: 'due', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });

    $('#rmp_supplier, #rmp_status, #rmp_pay_status').on('change', function () {
        rmpTable.ajax.reload();
    });
});

$(document).on('click', '.rmp-add-pay', function (e) {
    e.preventDefault();
    rmpPayId = $(this).data('id');
    $('#rmpPayRef').text($(this).data('ref') + ' — still due Rs. ' + $(this).data('due'));
    $('#rmpPayAmount').val($(this).data('due'));
    $('#rmpPayErr').text('');
    $('#rmpPayModal').addClass('show');
});

$('#rmpPaySave').on('click', function () {
    var btn = $(this);
    btn.prop('disabled', true);
    $.ajax({
        method: 'POST',
        url: "{{ url('inventory/purchases') }}/" + rmpPayId + "/payments",
        data: {
            _token: '{{ csrf_token() }}',
            amount: $('#rmpPayAmount').val(),
            paid_on: $('#rmpPayDate').val(),
            method: $('#rmpPayMethod').val(),
            note: $('#rmpPayNote').val()
        },
        success: function () {
            btn.prop('disabled', false);
            $('#rmpPayModal').removeClass('show');
            rmpTable.ajax.reload(null, false);
        },
        error: function (xhr) {
            btn.prop('disabled', false);
            var msg = 'Could not save the payment.';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                var key = Object.keys(xhr.responseJSON.errors)[0];
                msg = xhr.responseJSON.errors[key][0];
            }
            $('#rmpPayErr').text(msg);
        }
    });
});

$(document).on('click', '.rmp-view-pay', function (e) {
    e.preventDefault();
    var id = $(this).data('id');
    $('#rmpPayListTitle').text('Payments — ' + $(this).data('ref'));
    $('#rmpPayListBody').html('Loading…');
    $('#rmpPayListModal').addClass('show');
    $.get("{{ url('inventory/purchases') }}/" + id + "/payments", function (data) {
        var html = '<p><strong>Total</strong> Rs. ' + Number(data.total).toFixed(2)
            + ' · <strong>Paid</strong> Rs. ' + Number(data.paid).toFixed(2)
            + ' · <strong>Due</strong> Rs. ' + Number(data.due).toFixed(2) + '</p>';
        if (!data.payments.length) {
            html += '<p>No payment recorded yet.</p>';
        } else {
            html += '<table class="table table-bordered"><thead><tr><th>Date</th><th>Method</th><th>Amount</th><th>Note</th></tr></thead><tbody>';
            data.payments.forEach(function (p) {
                html += '<tr><td>' + p.paid_on + '</td><td>' + p.method + '</td><td>Rs. ' + p.amount + '</td><td>' + (p.note || '') + '</td></tr>';
            });
            html += '</tbody></table>';
        }
        $('#rmpPayListBody').html(html);
    });
});
</script>
@endsection
