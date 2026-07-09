@component('components.widget', ['class' => 'box-primary'])
<div class="row" style="margin-bottom:16px;">
    <div class="col-md-2 col-sm-4">
        <div style="background:#f3f4f6;border-radius:10px;padding:12px;text-align:center;">
            <div style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;">Jobs</div>
            <div style="font-size:20px;font-weight:900;">{{ $report['totals']['job_count'] }}</div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div style="background:#fef3c7;border-radius:10px;padding:12px;text-align:center;">
            <div style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;">Production Cost</div>
            <div style="font-size:18px;font-weight:900;color:#b45309;"><span class="display_currency" data-currency_symbol="true">{{ $report['totals']['total_cost'] }}</span></div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div style="background:#dbeafe;border-radius:10px;padding:12px;text-align:center;">
            <div style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;">Revenue</div>
            <div style="font-size:18px;font-weight:900;color:#1d4ed8;"><span class="display_currency" data-currency_symbol="true">{{ $report['totals']['total_revenue'] }}</span></div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div style="background:{{ $report['totals']['total_profit'] >= 0 ? '#d1fae5' : '#fee2e2' }};border-radius:10px;padding:12px;text-align:center;">
            <div style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;">Net Profit / Loss</div>
            <div style="font-size:18px;font-weight:900;color:{{ $report['totals']['total_profit'] >= 0 ? '#047857' : '#dc2626' }};"><span class="display_currency" data-currency_symbol="true">{{ $report['totals']['total_profit'] }}</span></div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div style="background:#dcfce7;border-radius:10px;padding:12px;text-align:center;">
            <div style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;">Completed</div>
            <div style="font-size:20px;font-weight:900;color:#15803d;">{{ $report['completed_count'] }}</div>
        </div>
    </div>
    <div class="col-md-2 col-sm-4">
        <div style="background:#ede9fe;border-radius:10px;padding:12px;text-align:center;">
            <div style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;">Ongoing</div>
            <div style="font-size:20px;font-weight:900;color:#5b21b6;">{{ $report['ongoing_count'] }}</div>
        </div>
    </div>
</div>

@if($report['by_stage']->count())
<h4 style="font-size:14px;font-weight:800;margin:0 0 10px;">Summary by Stage</h4>
<div class="table-responsive" style="margin-bottom:20px;">
    <table class="table table-bordered table-condensed">
        <thead>
            <tr>
                <th>Stage</th>
                <th>Jobs</th>
                <th>Cost</th>
                <th>Revenue</th>
                <th>Profit / Loss</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['by_stage'] as $stageRow)
            <tr>
                <td><strong style="color:{{ \App\ProductionJob::stageColor($stageRow['stage']) }};">{{ $stageRow['label'] }}</strong></td>
                <td>{{ $stageRow['job_count'] }}</td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $stageRow['total_cost'] }}</span></td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $stageRow['total_revenue'] }}</span></td>
                <td style="font-weight:700;color:{{ $stageRow['total_profit'] >= 0 ? '#15803d' : '#dc2626' }};">
                    <span class="display_currency" data-currency_symbol="true">{{ $stageRow['total_profit'] }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<h4 style="font-size:14px;font-weight:800;margin:0 0 10px;">Job Details</h4>
<p class="text-muted" style="font-size:12px;margin-bottom:12px;">
    Profit / Loss = Revenue (inquiry payment + product sales) − Production Cost (materials + section labor).
</p>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Job #</th>
                <th>Title</th>
                <th>Customer</th>
                <th>Stage</th>
                <th>Materials</th>
                <th>Labor</th>
                <th>Total Cost</th>
                <th>Revenue</th>
                <th>Profit / Loss</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
            @php $job = $row['job']; @endphp
            <tr>
                <td><strong>{{ $job->job_number }}</strong></td>
                <td>{{ $job->title }}</td>
                <td>{{ $job->customer_name }}</td>
                <td><span style="font-size:11px;font-weight:700;color:{{ \App\ProductionJob::stageColor($job->current_stage) }};">{{ $stages[$job->current_stage] ?? $job->current_stage }}</span></td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $row['material_cost'] }}</span></td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $row['stage_cost'] }}</span></td>
                <td><strong><span class="display_currency" data-currency_symbol="true">{{ $row['total_cost'] }}</span></strong></td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $row['revenue'] }}</span></td>
                <td style="font-weight:800;color:{{ $row['profit'] >= 0 ? '#15803d' : '#dc2626' }};">
                    <span class="display_currency" data-currency_symbol="true">{{ $row['profit'] }}</span>
                </td>
                <td>
                    <a href="{{ route('production.detail', $job) }}" class="btn btn-xs btn-default">Costs</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="10" class="text-center text-muted">No jobs found.</td></tr>
            @endforelse
        </tbody>
        @if($report['rows']->count())
        <tfoot>
            <tr style="font-weight:800;background:#f9fafb;">
                <td colspan="4">Totals</td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $report['totals']['material_cost'] }}</span></td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $report['totals']['stage_cost'] }}</span></td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $report['totals']['total_cost'] }}</span></td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $report['totals']['total_revenue'] }}</span></td>
                <td style="color:{{ $report['totals']['total_profit'] >= 0 ? '#15803d' : '#dc2626' }};"><span class="display_currency" data-currency_symbol="true">{{ $report['totals']['total_profit'] }}</span></td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>
@endcomponent
