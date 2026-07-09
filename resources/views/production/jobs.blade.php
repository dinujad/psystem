@extends('layouts.app')
@section('title', 'All Production Jobs')

@section('css')
<style>
.pjobs-page { padding: 0 20px 60px; }
.pjobs-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin: 20px 0 18px; }
.pjobs-title { font-size: 20px; font-weight: 800; color: #1e1b4b; }
.pjobs-bar { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px 16px; display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; align-items: center; }
.pjobs-bar input, .pjobs-bar select { border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 13px; background: #f9fafb; }
.pjobs-btn { background: #7c5cfc; color: #fff; border: none; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
.pjobs-btn.outline { background: #fff; color: #7c5cfc; border: 1.5px solid #7c5cfc; }
.pjobs-table-wrap { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
.pjobs-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.pjobs-table th { text-align: left; padding: 12px 14px; background: #f9fafb; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; border-bottom: 1px solid #e5e7eb; }
.pjobs-table td { padding: 12px 14px; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: middle; }
.pjobs-table tr:hover td { background: #fafafa; }
.pjobs-num { font-weight: 800; color: #7c5cfc; text-decoration: none; }
.pjobs-num:hover { text-decoration: underline; }
.pjobs-stage { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.pjobs-view { font-size: 12px; font-weight: 700; color: #7c5cfc; text-decoration: none; background: #ede9fe; padding: 5px 12px; border-radius: 8px; }
.pjobs-view:hover { background: #ddd6fe; text-decoration: none; }
.pjobs-empty { text-align: center; padding: 40px; color: #9ca3af; }

.pjobs-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.pjobs-tab  { display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px; border-radius: 22px; font-size: 13px; font-weight: 700; text-decoration: none; background: #fff; border: 1px solid #e5e7eb; color: #374151; transition: all .15s; }
.pjobs-tab:hover { text-decoration: none; border-color: #7c5cfc; color: #7c5cfc; }
.pjobs-tab .badge { font-size: 11px; font-weight: 800; padding: 2px 9px; border-radius: 20px; background: #f3f4f6; color: #6b7280; }
.pjobs-tab.active { color: #fff; }
.pjobs-tab.active .badge { background: rgba(255,255,255,.25); color: #fff; }
.pjobs-tab.active.all       { background: #4f46e5; border-color: #4f46e5; }
.pjobs-tab.active.ongoing   { background: #2563eb; border-color: #2563eb; }
.pjobs-tab.active.completed { background: #16a34a; border-color: #16a34a; }
.pjobs-done-badge { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; color:#16a34a; }
.pjobs-convert { font-size:12px; font-weight:700; color:#fff; background:#16a34a; border:none; padding:5px 12px; border-radius:8px; cursor:pointer; }
.pjobs-convert:hover { background:#15803d; }
.pjobs-converted { font-size:11px; font-weight:700; color:#16a34a; background:#dcfce7; padding:5px 10px; border-radius:8px; text-decoration:none; }
.pjobs-converted:hover { background:#bbf7d0; text-decoration:none; color:#15803d; }
.pjobs-actions { display:flex; flex-wrap:wrap; gap:6px; align-items:center; }
.pjobs-cost { font-size:12px; font-weight:700; color:#b45309; }
.pjobs-profit { font-size:12px; font-weight:800; }
.pjobs-profit.pos { color:#15803d; }
.pjobs-profit.neg { color:#dc2626; }
.pjobs-cost-link { font-size:11px; font-weight:700; color:#7c5cfc; text-decoration:none; }
.pjobs-cost-link:hover { text-decoration:underline; }
</style>
@include('production.partials.convert-product-styles')
@endsection

@section('content')
<div class="pjobs-page">
    <div class="pjobs-head">
        <div class="pjobs-title">All Production Jobs</div>
        <a href="{{ route('production.index') }}" class="pjobs-btn outline">← Board</a>
        @if($canViewCosts ?? false)
        <a href="{{ route('reports.production-report') }}" class="pjobs-btn outline">Profit / Loss Report</a>
        @endif
    </div>

    <div class="pjobs-tabs">
        <a href="{{ route('production.jobs', ['status' => 'all', 'q' => $search, 'stage' => $stage]) }}"
           class="pjobs-tab all {{ $status === 'all' ? 'active' : '' }}">
            All Jobs <span class="badge">{{ $statusCounts['all'] }}</span>
        </a>
        <a href="{{ route('production.jobs', ['status' => 'ongoing', 'q' => $search, 'stage' => $stage]) }}"
           class="pjobs-tab ongoing {{ $status === 'ongoing' ? 'active' : '' }}">
            🔄 Ongoing <span class="badge">{{ $statusCounts['ongoing'] }}</span>
        </a>
        <a href="{{ route('production.jobs', ['status' => 'completed', 'q' => $search, 'stage' => $stage]) }}"
           class="pjobs-tab completed {{ $status === 'completed' ? 'active' : '' }}">
            ✅ Completed <span class="badge">{{ $statusCounts['completed'] }}</span>
        </a>
    </div>

    <form method="GET" class="pjobs-bar">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="text" name="q" value="{{ $search }}" placeholder="Search job number, customer, title…" style="flex:1;min-width:200px;">
        <select name="stage">
            <option value="all" @selected(! $stage || $stage === 'all')>All Stages</option>
            @foreach($stages as $key => $label)
            <option value="{{ $key }}" @selected($stage === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="pjobs-btn">Search</button>
        @if($search || ($stage && $stage !== 'all'))
        <a href="{{ route('production.jobs', ['status' => $status]) }}" class="pjobs-btn outline">Clear</a>
        @endif
    </form>

    <div class="pjobs-table-wrap">
        <table class="pjobs-table">
            <thead>
                <tr>
                    <th>Job No</th>
                    <th>Customer</th>
                    <th>Title</th>
                    <th>Stage</th>
                    <th>Priority</th>
                    <th>Due</th>
                    @if($canViewCosts ?? false)
                    <th>Cost</th>
                    <th>Profit / Loss</th>
                    @endif
                    <th>{{ $status === 'completed' ? 'Completed' : 'Created' }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobs as $job)
                @php
                    $isDone = $job->current_stage === 'completed';
                    $sc = \App\ProductionJob::stageColor($job->current_stage);
                    $sl = $stages[$job->current_stage] ?? $job->current_stage;
                @endphp
                <tr onclick="if(!event.target.closest('.pjobs-actions,.pjobs-num')) window.location='{{ route('production.show', $job) }}'" style="cursor:pointer;">
                    <td><a href="{{ route('production.show', $job) }}" class="pjobs-num" onclick="event.stopPropagation()">{{ $job->job_number }}</a></td>
                    <td>{{ $job->customer_name }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($job->title, 40) }}</td>
                    <td>
                        @if($isDone)
                        <span class="pjobs-stage" style="background:#dcfce7;color:#16a34a;">✅ {{ $sl }}</span>
                        @else
                        <span class="pjobs-stage" style="background:{{ $sc }}22;color:{{ $sc }};">{{ $sl }}</span>
                        @endif
                    </td>
                    <td>{{ ucfirst($job->priority) }}</td>
                    <td>{{ $job->due_date ? $job->due_date->format('d M Y') : '—' }}</td>
                    @if($canViewCosts ?? false)
                    @php $jc = $jobCosts[$job->id] ?? null; @endphp
                    <td>
                        @if($jc && $jc['total_cost'] > 0)
                        <span class="pjobs-cost">Rs {{ number_format($jc['total_cost'], 2) }}</span>
                        <br><a href="{{ route('production.detail', $job) }}" class="pjobs-cost-link">Detail</a>
                        @else
                        <span style="color:#9ca3af;">—</span>
                        @endif
                    </td>
                    <td>
                        @if($jc && ($jc['revenue'] > 0 || $jc['total_cost'] > 0))
                        <span class="pjobs-profit {{ $jc['profit'] >= 0 ? 'pos' : 'neg' }}">Rs {{ number_format($jc['profit'], 2) }}</span>
                        @if($jc['revenue'] > 0)
                        <br><small style="color:#6b7280;">Rev Rs {{ number_format($jc['revenue'], 2) }}</small>
                        @endif
                        @else
                        <span style="color:#9ca3af;">—</span>
                        @endif
                    </td>
                    @endif
                    <td>
                        @if($isDone && $job->latestStage)
                        <span class="pjobs-done-badge">{{ $job->latestStage->started_at?->format('d M Y') ?? $job->updated_at->format('d M Y') }}</span>
                        @else
                        {{ $job->created_at->format('d M Y') }}
                        @endif
                    </td>
                    <td>
                        <div class="pjobs-actions" onclick="event.stopPropagation()">
                            <a href="{{ route('production.show', $job) }}" class="pjobs-view">View →</a>
                            @if($isDone)
                                @if($job->isConverted() && $job->product_id)
                                <a href="{{ route('products.index', ['view_product' => $job->product_id]) }}" class="pjobs-converted" title="Converted {{ $job->converted_at?->format('d M Y') }}">✓ Product</a>
                                @else
                                <button type="button" class="pjobs-convert" onclick="openConvertModal({{ $job->id }})">Convert to Product</button>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="{{ ($canViewCosts ?? false) ? 10 : 8 }}" class="pjobs-empty">
                    @if($status === 'completed')
                        No completed jobs yet.
                    @elseif($status === 'ongoing')
                        No ongoing jobs right now.
                    @else
                        No jobs found.
                    @endif
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($jobs->hasPages())
    <div style="margin-top:16px;">{{ $jobs->links() }}</div>
    @endif
</div>

@include('production.partials.convert-product-modal')
@endsection

@section('javascript')
@include('production.partials.convert-product-script')
@endsection
