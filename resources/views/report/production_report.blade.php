@extends('layouts.app')
@section('title', 'Production Report')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Production Report</h1>
    <p class="text-muted" style="margin-top:4px;">Full production overview with cost, revenue, and profit/loss per job.</p>
</section>

<section class="content">
    <div class="row no-print">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    <label>Status</label>
                    <select class="form-control" id="pr_status">
                        <option value="all">All Jobs</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>
            <div class="col-md-5">
                <div class="form-group">
                    <label>@lang('report.date_range')</label>
                    <input type="text" class="form-control" id="pr_date_range" readonly placeholder="Select date range">
                </div>
            </div>
            <div class="col-sm-12">
                <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-sm tw-text-white pull-right" id="pr_apply">@lang('report.apply_filters')</button>
            </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12" id="pr_data"></div>
    </div>
</section>
@endsection

@section('javascript')
<script>
$(function(){
    var start = moment().startOf('month').format('YYYY-MM-DD');
    var end = moment().endOf('month').format('YYYY-MM-DD');

    $('#pr_date_range').daterangepicker({
        startDate: moment().startOf('month'),
        endDate: moment().endOf('month'),
        locale: { format: 'YYYY-MM-DD' }
    }, function(s, e){
        start = s.format('YYYY-MM-DD');
        end = e.format('YYYY-MM-DD');
        loadProductionReport();
    });

    $('#pr_status').change(loadProductionReport);
    $('#pr_apply').click(loadProductionReport);

    function loadProductionReport(){
        $.ajax({
            url: '{{ route("reports.production-report") }}',
            data: {
                start_date: start,
                end_date: end,
                status: $('#pr_status').val()
            },
            success: function(html){
                $('#pr_data').html(html);
                __currency_convert_recursively($('#pr_data'));
            }
        });
    }

    loadProductionReport();
});
</script>
@endsection
