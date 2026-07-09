@component('components.widget', ['class' => 'box-primary'])
<div class="row" style="margin-bottom:16px;">
    <div class="col-md-3 col-sm-6">
        <div style="background:#f5f3ff;border-radius:10px;padding:14px;">
            <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;">Total Jobs</div>
            <div style="font-size:22px;font-weight:900;color:#5b21b6;">{{ $report['totals']['job_count'] }}</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div style="background:#fef3c7;border-radius:10px;padding:14px;">
            <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;">Materials Cost</div>
            <div style="font-size:22px;font-weight:900;color:#b45309;"><span class="display_currency" data-currency_symbol="true">{{ $report['totals']['material_cost'] }}</span></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div style="background:#dbeafe;border-radius:10px;padding:14px;">
            <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;">Section Labor</div>
            <div style="font-size:22px;font-weight:900;color:#1d4ed8;"><span class="display_currency" data-currency_symbol="true">{{ $report['totals']['stage_cost'] }}</span></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div style="background:#d1fae5;border-radius:10px;padding:14px;">
            <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;">Total Production Cost</div>
            <div style="font-size:22px;font-weight:900;color:#047857;"><span class="display_currency" data-currency_symbol="true">{{ $report['totals']['total_cost'] }}</span></div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped" id="production_costs_table">
        <thead>
            <tr>
                <th>Job #</th>
                <th>Title / Customer</th>
                <th>Stage</th>
                <th>Materials</th>
                <th>Design</th>
                <th>Production</th>
                <th>Quality</th>
                <th>Dispatch</th>
                <th>Total Cost</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
            @php $job = $row['job']; $bd = $row['stage_breakdown']; @endphp
            <tr>
                <td><strong>{{ $job->job_number }}</strong></td>
                <td>
                    <div>{{ $job->title }}</div>
                    <small class="text-muted">{{ $job->customer_name }}</small>
                </td>
                <td>
                    <span style="font-size:11px;font-weight:700;color:{{ \App\ProductionJob::stageColor($job->current_stage) }};">
                        {{ $stages[$job->current_stage] ?? $job->current_stage }}
                    </span>
                </td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $row['material_cost'] }}</span></td>
                <td>@if(!empty($bd['design']))<span class="display_currency" data-currency_symbol="true">{{ $bd['design']['amount'] }}</span>@else — @endif</td>
                <td>@if(!empty($bd['production']))<span class="display_currency" data-currency_symbol="true">{{ $bd['production']['amount'] }}</span>@else — @endif</td>
                <td>@if(!empty($bd['quality']))<span class="display_currency" data-currency_symbol="true">{{ $bd['quality']['amount'] }}</span>@else — @endif</td>
                <td>@if(!empty($bd['dispatch']))<span class="display_currency" data-currency_symbol="true">{{ $bd['dispatch']['amount'] }}</span>@else — @endif</td>
                <td><strong><span class="display_currency" data-currency_symbol="true">{{ $row['total_cost'] }}</span></strong></td>
                <td>
                    <a href="{{ route('production.detail', $job) }}" class="btn btn-xs btn-default">Cost Detail</a>
                    <a href="{{ route('production.show', $job) }}" class="btn btn-xs btn-primary">Job</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="10" class="text-center text-muted">No production jobs found for selected filters.</td></tr>
            @endforelse
        </tbody>
        @if($report['rows']->count())
        <tfoot>
            <tr style="font-weight:800;background:#f9fafb;">
                <td colspan="3">Totals</td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $report['totals']['material_cost'] }}</span></td>
                <td colspan="4"></td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $report['totals']['total_cost'] }}</span></td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>
@endcomponent
