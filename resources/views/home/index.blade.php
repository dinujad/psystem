@extends('layouts.app')
@section('title', __('home.home'))

@section('content')

    <div class="pw-dashboard tw-pb-6 xl:tw-pb-0">
        {{-- Hero header --}}
        <div class="pw-dash-hero">
            <div class="tw-px-5 tw-pt-5 tw-pb-5">
            {{-- <div class="sm:tw-flex sm:tw-items-center sm:tw-justify-between sm:tw-gap-12">
                <h1 class="tw-text-2xl tw-font-medium tw-tracking-tight tw-text-white">
                    {{ __('home.welcome_message', ['name' => Session::get('user.first_name')]) }}
                </h1>
            </div> --}}
                    <div class="sm:tw-flex sm:tw-items-center sm:tw-justify-between sm:tw-gap-6">
                        <div class="tw-mt-2 sm:tw-w-1/2 md:tw-w-1/2">
                            <p class="pw-dash-eyebrow">PrintWorks Dashboard</p>
                            <h1 class="pw-dash-title">
                                {{ __('home.welcome_message', ['name' => Session::get('user.first_name')]) }}
                            </h1>
                            <p class="pw-dash-subtitle">{{ __('home.dashboard') }} overview &amp; business insights</p>
                        </div>
    
                        @if (auth()->user()->can('dashboard.data'))
                            @if ($is_admin)
                                <div class="tw-mt-2 sm:tw-w-1/3 md:tw-w-1/4 pw-dash-filter">
                                    @if (count($all_locations) > 1)
                                        {!! Form::select('dashboard_location', $all_locations, null, [
                                            'class' => 'form-control select2',
                                            'placeholder' => __('lang_v1.select_location'),
                                            'id' => 'dashboard_location',
                                        ]) !!}
                                    @endif
                                </div>
            
                                <div class="tw-mt-2 sm:tw-w-1/3 md:tw-w-1/4 tw-text-right">
                                    @if ($is_admin)
                                        <button type="button" id="dashboard_date_filter"
                                            class="pw-dash-date-btn tw-inline-flex tw-items-center tw-justify-center tw-w-full tw-gap-1 tw-px-4 tw-py-2.5 tw-text-sm tw-font-medium tw-transition-all tw-duration-200 tw-rounded-xl sm:tw-w-auto">
                                            <svg aria-hidden="true" class="tw-size-5" xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path
                                                    d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" />
                                                <path d="M16 3v4" />
                                                <path d="M8 3v4" />
                                                <path d="M4 11h16" />
                                                <path d="M7 14h.013" />
                                                <path d="M10.01 14h.005" />
                                                <path d="M13.01 14h.005" />
                                                <path d="M16.015 14h.005" />
                                                <path d="M13.015 17h.005" />
                                                <path d="M7.01 17h.005" />
                                                <path d="M10.01 17h.005" />
                                            </svg>
                                            <span>
                                                {{ __('messages.filter_by_date') }}
                                            </span>
                                            <svg aria-hidden="true" class="tw-size-4" xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M6 9l6 6l6 -6" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            @endif
                        @endif
                    </div>
                    </div>{{-- end hero inner padding --}}
        </div>{{-- end hero --}}

        @if (auth()->user()->can('dashboard.data'))
            @if ($is_admin)
                <div class="tw-px-5 pw-dash-body">
                    <div class="tw-grid tw-grid-cols-2 tw-gap-3 md:tw-gap-4 lg:tw-grid-cols-4 pw-stats-grid">
                            
                                <div class="pw-stat-card">
                                    <div class="tw-p-4 sm:tw-p-5">
                                        <div class="tw-flex tw-items-center tw-gap-4">
                                            <div class="pw-stat-icon">
                                                <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                                    stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                                    <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                                    <path d="M17 17h-11v-14h-2" />
                                                    <path d="M6 5l14 1l-1 7h-13" />
                                                </svg>
                                            </div>
                                            <div class="tw-flex-1 tw-min-w-0">
                                                <p class="pw-stat-label">{{ __('home.total_sell') }}</p>
                                                <p class="total_sell pw-stat-value"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="pw-stat-card">
                                    <div class="tw-p-4 sm:tw-p-5">
                                        <div class="tw-flex tw-items-center tw-gap-4">
                                            <div class="pw-stat-icon">
                                                <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                                    stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path
                                                        d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2">
                                                    </path>
                                                    <path
                                                        d="M14.8 8a2 2 0 0 0 -1.8 -1h-2a2 2 0 1 0 0 4h2a2 2 0 1 1 0 4h-2a2 2 0 0 1 -1.8 -1">
                                                    </path>
                                                    <path d="M12 6v10"></path>
                                                </svg>
                                            </div>

                                            <div class="tw-flex-1 tw-min-w-0">
                                                <p class="pw-stat-label">
                                                    {{ __('lang_v1.net') }} @show_tooltip(__('lang_v1.net_home_tooltip'))
                                                </p>
                                                <p class="net pw-stat-value"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="pw-stat-card">
                                    <div class="tw-p-4 sm:tw-p-5">
                                        <div class="tw-flex tw-items-center tw-gap-4">
                                            <div class="pw-stat-icon">
                                                <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                                    stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                                    <path d="M9 7l1 0" /><path d="M9 13l6 0" /><path d="M13 17l2 0" />
                                                </svg>
                                            </div>
                                            <div class="tw-flex-1 tw-min-w-0">
                                                <p class="pw-stat-label">{{ __('home.invoice_due') }}</p>
                                                <p class="invoice_due pw-stat-value"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="pw-stat-card">
                                    <div class="tw-p-4 sm:tw-p-5">
                                        <div class="tw-flex tw-items-center tw-gap-4">
                                            <div class="pw-stat-icon">
                                                <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                                    stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M21 7l-18 0" /><path d="M18 10l3 -3l-3 -3" />
                                                    <path d="M6 20l-3 -3l3 -3" /><path d="M3 17l18 0" />
                                                </svg>
                                            </div>
                                            <div class="tw-flex-1 tw-min-w-0">
                                                <p class="pw-stat-label">
                                                    {{ __('lang_v1.total_sell_return') }}
                                                    <i class="fa fa-info-circle text-info hover-q no-print" aria-hidden="true" data-container="body"
                                                    data-toggle="popover" data-placement="auto bottom" id="total_srp"
                                                    data-value="{{ __('lang_v1.total_sell_return') }}-{{ __('lang_v1.total_sell_return_paid') }}"
                                                    data-content="" data-html="true" data-trigger="hover"></i>
                                                </p>
                                                <p class="total_sell_return pw-stat-value"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <div class="pw-stat-card">
                                <div class="tw-p-4 sm:tw-p-5">
                                    <div class="tw-flex tw-items-center tw-gap-4">
                                        <div class="pw-stat-icon">
                                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M12 3v12"></path>
                                                <path d="M16 11l-4 4l-4 -4"></path>
                                                <path d="M3 12a9 9 0 0 0 18 0"></path>
                                            </svg>
                                        </div>
                                        <div class="tw-flex-1 tw-min-w-0">
                                            <p class="pw-stat-label">{{ __('home.total_purchase') }}</p>
                                            <p class="total_purchase pw-stat-value"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="pw-stat-card">
                                <div class="tw-p-4 sm:tw-p-5">
                                    <div class="tw-flex tw-items-center tw-gap-4">
                                        <div class="pw-stat-icon">
                                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M12 9v4" />
                                                <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" />
                                                <path d="M12 16h.01" />
                                            </svg>
                                        </div>
                                        <div class="tw-flex-1 tw-min-w-0">
                                            <p class="pw-stat-label">{{ __('home.purchase_due') }}</p>
                                            <p class="purchase_due pw-stat-value"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pw-stat-card">
                                <div class="tw-p-4 sm:tw-p-5">
                                    <div class="tw-flex tw-items-center tw-gap-4">
                                        <div class="pw-stat-icon">
                                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2" />
                                                <path d="M15 14v-2a2 2 0 0 0 -2 -2h-4l2 -2m0 4l-2 -2" />
                                            </svg>
                                        </div>
                                        <div class="tw-flex-1 tw-min-w-0">
                                            <p class="pw-stat-label">
                                                {{ __('lang_v1.total_purchase_return') }}
                                                <i class="fa fa-info-circle text-info hover-q no-print" aria-hidden="true" data-container="body"
                                                data-toggle="popover" data-placement="auto bottom" id="total_prp"
                                                data-value="{{ __('lang_v1.total_purchase_return') }}-{{ __('lang_v1.total_purchase_return_paid') }}"
                                                data-content="" data-html="true" data-trigger="hover"></i>
                                            </p>
                                            <p class="total_purchase_return pw-stat-value"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pw-stat-card">
                                <div class="tw-p-4 sm:tw-p-5">
                                    <div class="tw-flex tw-items-center tw-gap-4">
                                        <div class="pw-stat-icon">
                                            <svg aria-hidden="true" class="tw-w-6 tw-h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"></path>
                                                <path d="M14.8 8a2 2 0 0 0 -1.8 -1h-2a2 2 0 1 0 0 4h2a2 2 0 1 1 0 4h-2a2 2 0 0 1 -1.8 -1"></path>
                                                <path d="M12 6v10"></path>
                                            </svg>
                                        </div>
                                        <div class="tw-flex-1 tw-min-w-0">
                                            <p class="pw-stat-label">{{ __('lang_v1.expense') }}</p>
                                            <p class="total_expense pw-stat-value"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </div>{{-- end pw-stats-grid --}}
                </div>
            @endif

                @if(!empty($showProduction))
                <div class="tw-px-5 pw-active-jobs">
                    <div class="pw-jobs-card">
                        <div class="pw-jobs-head">
                            <div>
                                <h3 class="pw-jobs-title">Active Production Jobs</h3>
                                <p class="pw-jobs-sub">Current workflow status overview</p>
                            </div>
                            <a href="{{ route('production.index') }}" class="pw-jobs-link">View Board →</a>
                        </div>

                        {{-- Workflow stage counts --}}
                        <div class="pw-jobs-stages">
                            @foreach($productionStages as $stageKey => $stageLabel)
                            @if($stageKey !== 'completed')
                            @php $cnt = $stageCounts[$stageKey] ?? 0; @endphp
                            <a href="{{ route('production.index', ['stage' => $stageKey]) }}" class="pw-jobs-stage {{ $cnt > 0 ? 'has-jobs' : '' }}">
                                <span class="pw-jobs-stage-dot" style="background:{{ \App\ProductionJob::stageColor($stageKey) }}"></span>
                                <span class="pw-jobs-stage-name">{{ $stageLabel }}</span>
                                <span class="pw-jobs-stage-count">{{ $cnt }}</span>
                            </a>
                            @endif
                            @endforeach
                        </div>

                        @if($activeJobs->count())
                        <div class="pw-jobs-table-wrap">
                            <table class="pw-jobs-table">
                                <thead>
                                    <tr>
                                        <th>Job No</th>
                                        <th>Customer</th>
                                        <th>Title</th>
                                        <th>Stage</th>
                                        <th>Priority</th>
                                        <th>Due</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activeJobs as $job)
                                    <tr>
                                        <td><strong>{{ $job->job_number }}</strong></td>
                                        <td>{{ $job->customer_name }}</td>
                                        <td>{{ Str::limit($job->title, 30) }}</td>
                                        <td>
                                            <span class="pw-jobs-badge" style="background:{{ \App\ProductionJob::stageColor($job->current_stage) }}22;color:{{ \App\ProductionJob::stageColor($job->current_stage) }}">
                                                {{ $productionStages[$job->current_stage] ?? $job->current_stage }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="pw-jobs-priority" style="color:{{ \App\ProductionJob::priorityColor($job->priority) }}">{{ ucfirst($job->priority) }}</span>
                                        </td>
                                        <td class="{{ $job->due_date && $job->due_date->isPast() ? 'pw-jobs-overdue' : '' }}">
                                            {{ $job->due_date ? $job->due_date->format('d M Y') : '—' }}
                                        </td>
                                        <td><a href="{{ route('production.show', $job) }}" class="pw-jobs-view">View</a></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="pw-jobs-empty">No active jobs right now. Jobs appear here once sent to production.</div>
                        @endif
                    </div>
                </div>
                @endif

            <div class="tw-px-5 tw-pt-4 tw-pb-6 pw-dash-charts">
            <div class="tw-grid tw-grid-cols-1 tw-gap-4 lg:tw-grid-cols-2">
                @if (auth()->user()->can('sell.view') || auth()->user()->can('direct_sell.view'))
                    @if (!empty($all_locations))
                        <div class="pw-chart-card lg:tw-col-span-2">
                            <div class="tw-p-5 sm:tw-p-6">
                                <div class="pw-chart-head">
                                    <div class="pw-chart-head-icon">
                                        <svg aria-hidden="true" class="tw-size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                            <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                            <path d="M17 17h-11v-14h-2"></path>
                                            <path d="M6 5l14 1l-1 7h-13"></path>
                                        </svg>
                                    </div>
                                    <h3 class="pw-chart-title">{{ __('home.sells_last_30_days') }}</h3>
                                </div>
                                <div class="pw-chart-body">
                                    {!! $sells_chart_1->container() !!}
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- @if (!empty($widgets['after_sales_last_30_days']))
                        @foreach ($widgets['after_sales_last_30_days'] as $widget)
                            {!! $widget !!}
                        @endforeach
                    @endif --}}
                    @if (!empty($all_locations))
                        <div class="pw-chart-card lg:tw-col-span-2">
                            <div class="tw-p-5 sm:tw-p-6">
                                <div class="pw-chart-head">
                                    <div class="pw-chart-head-icon">
                                        <svg aria-hidden="true" class="tw-size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                            <path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                            <path d="M17 17h-11v-14h-2"></path>
                                            <path d="M6 5l14 1l-1 7h-13"></path>
                                        </svg>
                                    </div>
                                    <h3 class="pw-chart-title">{{ __('home.sells_current_fy') }}</h3>
                                </div>
                                <div class="pw-chart-body">
                                    {!! $sells_chart_2->container() !!}
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
                {{-- @if (!empty($widgets['after_sales_current_fy']))
                    @foreach ($widgets['after_sales_current_fy'] as $widget)
                        {!! $widget !!}
                    @endforeach
                @endif --}}
                @if (auth()->user()->can('sell.view') || auth()->user()->can('direct_sell.view'))
                    <div
                        class="tw-transition-all lg:tw-col-span-1 tw-duration-200 tw-bg-white tw-shadow-sm tw-rounded-xl tw-ring-1 hover:tw-shadow-md hover:tw--translate-y-0.5 tw-ring-gray-200">
                        <div class="tw-p-4 sm:tw-p-5">
                            <div class="tw-flex tw-items-center tw-gap-2.5">
                                <div
                                    class="tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-w-10 tw-h-10">
                                    <svg aria-hidden="true" class="tw-text-yellow-500 tw-size-5 tw-shrink-0"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M12 9v4"></path>
                                        <path
                                            d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z">
                                        </path>
                                        <path d="M12 16h.01"></path>
                                    </svg>
                                </div>
                                <div class="tw-flex tw-items-center tw-flex-1 tw-min-w-0 tw-gap-1">
                                    <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
                                        <h3 class="tw-font-bold tw-text-base lg:tw-text-xl">
                                            {{ __('lang_v1.sales_payment_dues') }}
                                            @show_tooltip(__('lang_v1.tooltip_sales_payment_dues'))
                                        </h3>
                                    </div>
                                    <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
                                        {!! Form::select('sales_payment_dues_location', $all_locations, null, [
                                            'class' => 'form-control select2',
                                            'placeholder' => __('lang_v1.select_location'),
                                            'id' => 'sales_payment_dues_location',
                                        ]) !!}
                                    </div>
                                </div>
                            </div>


                            <div class="tw-flow-root tw-mt-5  tw-border-gray-200">
                                <div class="tw--mx-4 tw--my-2 tw-overflow-x-auto sm:tw--mx-5">
                                    <div class="tw-inline-block tw-min-w-full tw-py-2 tw-align-middle sm:tw-px-5">
                                        <table class="table table-bordered table-striped" id="sales_payment_dues_table"
                                            style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>@lang('contact.customer')</th>
                                                    <th>@lang('sale.invoice_no')</th>
                                                    <th>@lang('home.due_amount')</th>
                                                    <th>@lang('messages.action')</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                @can('purchase.view')
                    <div
                        class="tw-transition-all lg:tw-col-span-1 tw-duration-200 tw-bg-white tw-shadow-sm tw-rounded-xl tw-ring-1 hover:tw-shadow-md hover:tw--translate-y-0.5 tw-ring-gray-200">
                        <div class="tw-p-4 sm:tw-p-5">
                            <div class="tw-flex tw-items-center tw-gap-2.5">
                                <div
                                    class="tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-w-10 tw-h-10">
                                    <svg aria-hidden="true" class="tw-text-yellow-500 tw-size-5 tw-shrink-0"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M12 9v4"></path>
                                        <path
                                            d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z">
                                        </path>
                                        <path d="M12 16h.01"></path>
                                    </svg>
                                </div>
                                <div class="tw-flex tw-items-center tw-flex-1 tw-min-w-0 tw-gap-1">
                                    <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
                                        <h3 class="tw-font-bold tw-text-base lg:tw-text-xl">
                                            {{ __('lang_v1.purchase_payment_dues') }}
                                            @show_tooltip(__('tooltip.payment_dues'))
                                        </h3>
                                    </div>
                                    <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
                                        @if (count($all_locations) > 1)
                                            {!! Form::select('purchase_payment_dues_location', $all_locations, null, [
                                                'class' => 'form-control select2 ',
                                                'placeholder' => __('lang_v1.select_location'),
                                                'id' => 'purchase_payment_dues_location',
                                            ]) !!}
                                        @endif
                                    </div>
                                </div>

                            </div>
                            <div class="tw-flow-root tw-mt-5  tw-border-gray-200">
                                <div class="tw--mx-4 tw--my-2 tw-overflow-x-auto sm:tw--mx-5">
                                    <div class="tw-inline-block tw-min-w-full tw-py-2 tw-align-middle sm:tw-px-5">
                                        <table class="table table-bordered table-striped" id="purchase_payment_dues_table"
                                            style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>@lang('purchase.supplier')</th>
                                                    <th>@lang('purchase.ref_no')</th>
                                                    <th>@lang('home.due_amount')</th>
                                                    <th>@lang('messages.action')</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endcan
                @can('stock_report.view')
                    <div
                        class="tw-transition-all lg:tw-col-span-2 tw-duration-200 tw-bg-white tw-shadow-sm tw-rounded-xl tw-ring-1 hover:tw-shadow-md hover:tw--translate-y-0.5 tw-ring-gray-200">
                        <div class="tw-p-4 sm:tw-p-5">
                            <div class="tw-flex tw-items-center tw-gap-2.5">
                                <div
                                    class="tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-w-10 tw-h-10">
                                    <svg aria-hidden="true" class="tw-text-yellow-500 tw-size-5 tw-shrink-0"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                                        <path d="M12 8v4"></path>
                                        <path d="M12 16h.01"></path>
                                    </svg>
                                </div>
                                <div class="tw-flex tw-items-center tw-flex-1 tw-min-w-0 tw-gap-1">
                                    <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
                                        <h3 class="tw-font-bold tw-text-base lg:tw-text-xl">
                                            {{ __('home.product_stock_alert') }}
                                            @show_tooltip(__('tooltip.product_stock_alert'))
                                        </h3>
                                    </div>
                                    <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
                                        @if (count($all_locations) > 1)
                                            {!! Form::select('stock_alert_location', $all_locations, null, [
                                                'class' => 'form-control select2',
                                                'placeholder' => __('lang_v1.select_location'),
                                                'id' => 'stock_alert_location',
                                            ]) !!}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="tw-flow-root tw-mt-5  tw-border-gray-200">
                                <div class="tw--mx-4 tw--my-2 tw-overflow-x-auto sm:tw--mx-5">
                                    <div class="tw-inline-block tw-min-w-full tw-py-2 tw-align-middle sm:tw-px-5">
                                        <table class="table table-bordered table-striped" id="stock_alert_table"
                                            style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>@lang('sale.product')</th>
                                                    <th>@lang('business.location')</th>
                                                    <th>@lang('report.current_stock')</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if (session('business.enable_product_expiry') == 1)
                        <div
                            class="tw-transition-all lg:tw-col-span-1 tw-duration-200 tw-bg-white tw-shadow-sm tw-rounded-xl tw-ring-1 hover:tw-shadow-md hover:tw--translate-y-0.5 tw-ring-gray-200">
                            <div class="tw-p-4 sm:tw-p-5">
                                <div class="tw-flex tw-items-center tw-gap-2.5">
                                    <div
                                        class="tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-w-10 tw-h-10">
                                        <svg aria-hidden="true" class="tw-text-yellow-500 tw-size-5 tw-shrink-0"
                                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2"
                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M12 9v4"></path>
                                            <path
                                                d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z">
                                            </path>
                                            <path d="M12 16h.01"></path>
                                        </svg>
                                    </div>
                                    <div class="tw-flex tw-items-center tw-flex-1 tw-min-w-0 tw-gap-1">
                                        <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
                                            <h3 class="tw-font-bold tw-text-base lg:tw-text-xl">
                                                {{ __('home.stock_expiry_alert') }}
                                                @show_tooltip(
                                                __('tooltip.stock_expiry_alert', [
                                                'days'
                                                =>session('business.stock_expiry_alert_days', 30) ]) )
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="tw-flow-root tw-mt-5  tw-border-gray-200">
                                    <div class="tw--mx-4 tw--my-2 tw-overflow-x-auto sm:tw--mx-5">
                                        <div class="tw-inline-block tw-min-w-full tw-py-2 tw-align-middle sm:tw-px-5">
                                            <input type="hidden" id="stock_expiry_alert_days"
                                                value="{{ \Carbon::now()->addDays(session('business.stock_expiry_alert_days', 30))->format('Y-m-d') }}">
                                            <table class="table table-bordered table-striped" id="stock_expiry_alert_table">
                                                <thead>
                                                    <tr>
                                                        <th>@lang('business.product')</th>
                                                        <th>@lang('business.location')</th>
                                                        <th>@lang('report.stock_left')</th>
                                                        <th>@lang('product.expires_in')</th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endcan
                @if (auth()->user()->can('so.view_all') || auth()->user()->can('so.view_own'))
                    <div
                        class="tw-transition-all lg:tw-col-span-2 tw-duration-200 tw-bg-white tw-shadow-sm tw-rounded-xl tw-ring-1 hover:tw-shadow-md hover:tw--translate-y-0.5 tw-ring-gray-200">
                        <div class="tw-p-4 sm:tw-p-5">
                            <div class="tw-flex tw-items-center tw-gap-2.5">
                                <div
                                    class="tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-w-10 tw-h-10">
                                    <svg aria-hidden="true" class="tw-text-yellow-500 tw-size-5 tw-shrink-0"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                                        <path d="M12 8v4"></path>
                                        <path d="M12 16h.01"></path>
                                    </svg>
                                </div>
                                <div class="tw-flex tw-items-center tw-flex-1 tw-min-w-0 tw-gap-1">
                                    <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
                                        <h3 class="tw-font-bold tw-text-base lg:tw-text-xl">
                                            {{ __('lang_v1.sales_order') }}
                                        </h3>
                                    </div>
                                    <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
                                        @if (count($all_locations) > 1)
                                            {!! Form::select('so_location', $all_locations, null, [
                                                'class' => 'form-control select2',
                                                'placeholder' => __('lang_v1.select_location'),
                                                'id' => 'so_location',
                                            ]) !!}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="tw-flow-root tw-mt-5  tw-border-gray-200">
                                <div class="tw--mx-4 tw--my-2 tw-overflow-x-auto sm:tw--mx-5">
                                    <div class="tw-inline-block tw-min-w-full tw-py-2 tw-align-middle sm:tw-px-5">
                                        <table class="table table-bordered table-striped ajax_view"
                                            id="sales_order_table">
                                            <thead>
                                                <tr>
                                                    <th>@lang('messages.action')</th>
                                                    <th>@lang('messages.date')</th>
                                                    <th>@lang('restaurant.order_no')</th>
                                                    <th>@lang('sale.customer_name')</th>
                                                    <th>@lang('lang_v1.contact_no')</th>
                                                    <th>@lang('sale.location')</th>
                                                    <th>@lang('sale.status')</th>
                                                    <th>@lang('lang_v1.shipping_status')</th>
                                                    <th>@lang('lang_v1.quantity_remaining')</th>
                                                    <th>@lang('lang_v1.added_by')</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                @if (
                    !empty($common_settings['enable_purchase_requisition']) &&
                        (auth()->user()->can('purchase_requisition.view_all') || auth()->user()->can('purchase_requisition.view_own')))
                    <div
                        class="tw-transition-all lg:tw-col-span-2 tw-duration-200 tw-bg-white tw-shadow-sm tw-rounded-xl tw-ring-1 hover:tw-shadow-md hover:tw--translate-y-0.5 tw-ring-gray-200">
                        <div class="tw-p-4 sm:tw-p-5">
                            <div class="tw-flex tw-items-center tw-gap-2.5">
                                <div
                                    class="tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-w-10 tw-h-10">
                                    <svg aria-hidden="true" class="tw-text-yellow-500 tw-size-5 tw-shrink-0"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M10 10v4a1 1 0 0 0 1 1h2a1 1 0 0 0 1 -1v-4"></path>
                                        <path d="M9 6h6"></path>
                                        <path d="M10 6v-2a1 1 0 0 1 1 -1h2a1 1 0 0 1 1 1v2"></path>
                                        <circle cx="12" cy="16" r="2"></circle>
                                        <path d="M5 20h14a2 2 0 0 0 2 -2v-10"></path>
                                        <path d="M15 16v4"></path>
                                        <path d="M9 20v-4"></path>
                                    </svg>
                                </div>
                                <div class="tw-flex tw-items-center tw-flex-1 tw-min-w-0 tw-gap-1">
                                    <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
                                        <h3 class="tw-font-bold tw-text-base lg:tw-text-xl">
                                            @lang('lang_v1.purchase_requisition')
                                        </h3>
                                    </div>
                                    <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
                                        @if (count($all_locations) > 1)
                                            @if (count($all_locations) > 1)
                                                {!! Form::select('pr_location', $all_locations, null, [
                                                    'class' => 'form-control select2',
                                                    'placeholder' => __('lang_v1.select_location'),
                                                    'id' => 'pr_location',
                                                ]) !!}
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="tw-flow-root tw-mt-5  tw-border-gray-200">
                                <div class="tw--mx-4 tw--my-2 tw-overflow-x-auto sm:tw--mx-5">
                                    <div class="tw-inline-block tw-min-w-full tw-py-2 tw-align-middle sm:tw-px-5">
                                        <table class="table table-bordered table-striped ajax_view"
                                            id="purchase_requisition_table" style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>@lang('messages.action')</th>
                                                    <th>@lang('messages.date')</th>
                                                    <th>@lang('purchase.ref_no')</th>
                                                    <th>@lang('purchase.location')</th>
                                                    <th>@lang('sale.status')</th>
                                                    <th>@lang('lang_v1.required_by_date')</th>
                                                    <th>@lang('lang_v1.added_by')</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if (
                    !empty($common_settings['enable_purchase_order']) &&
                        (auth()->user()->can('purchase_order.view_all') || auth()->user()->can('purchase_order.view_own')))

                    <div
                        class="tw-transition-all lg:tw-col-span-2 tw-duration-200 tw-bg-white tw-shadow-sm tw-rounded-xl tw-ring-1 hover:tw-shadow-md hover:tw--translate-y-0.5 tw-ring-gray-200">
                        <div class="tw-p-4 sm:tw-p-5">
                            <div class="tw-flex tw-items-center tw-gap-2.5">
                                <div
                                    class="tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-w-10 tw-h-10">
                                    <svg aria-hidden="true" class="tw-text-yellow-500 tw-size-5 tw-shrink-0"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <rect x="4" y="4" width="16" height="16" rx="2" />
                                        <line x1="4" y1="10" x2="20" y2="10" />
                                        <line x1="12" y1="4" x2="12" y2="20" />
                                        <line x1="12" y1="10" x2="16" y2="10" />
                                    </svg>
                                </div>
                                <div class="tw-flex tw-items-center tw-flex-1 tw-min-w-0 tw-gap-1">
                                    <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
                                        <h3 class="tw-font-bold tw-text-base lg:tw-text-xl">
                                            @lang('lang_v1.purchase_order')
                                        </h3>
                                    </div>
                                    <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
                                        @if (count($all_locations) > 1)
                                            {!! Form::select('po_location', $all_locations, null, [
                                                'class' => 'form-control select2',
                                                'placeholder' => __('lang_v1.select_location'),
                                                'id' => 'po_location',
                                            ]) !!}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="tw-flow-root tw-mt-5  tw-border-gray-200">
                                <div class="tw--mx-4 tw--my-2 tw-overflow-x-auto sm:tw--mx-5">
                                    <div class="tw-inline-block tw-min-w-full tw-py-2 tw-align-middle sm:tw-px-5">
                                        <table class="table table-bordered table-striped ajax_view"
                                            id="purchase_order_table" style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>@lang('messages.action')</th>
                                                    <th>@lang('messages.date')</th>
                                                    <th>@lang('purchase.ref_no')</th>
                                                    <th>@lang('purchase.location')</th>
                                                    <th>@lang('purchase.supplier')</th>
                                                    <th>@lang('sale.status')</th>
                                                    <th>@lang('lang_v1.quantity_remaining')</th>
                                                    <th>@lang('lang_v1.added_by')</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                @endif
                @if (auth()->user()->can('access_pending_shipments_only') ||
                        auth()->user()->can('access_shipping') ||
                        auth()->user()->can('access_own_shipping'))
                    <div
                        class="tw-transition-all lg:tw-col-span-2 tw-duration-200 tw-bg-white tw-shadow-sm tw-rounded-xl tw-ring-1 hover:tw-shadow-md hover:tw--translate-y-0.5 tw-ring-gray-200">
                        <div class="tw-p-4 sm:tw-p-5">
                            <div class="tw-flex tw-items-center tw-gap-2.5">
                                <div
                                    class="tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-w-10 tw-h-10">
                                    <svg aria-hidden="true" class="tw-text-yellow-500 tw-size-5 tw-shrink-0"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M7 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                        <path d="M17 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                        <path d="M5 17h-2v-4m-1 -8h11v12m-4 0h6m4 0h2v-6h-8m0 -5h5l3 5"></path>
                                        <path d="M3 9l4 0"></path>
                                    </svg>
                                </div>
                                <div class="tw-flex tw-items-center tw-flex-1 tw-min-w-0 tw-gap-1">
                                    <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
                                        <h3 class="tw-font-bold tw-text-base lg:tw-text-xl">
                                            @lang('lang_v1.pending_shipments')
                                        </h3>
                                    </div>
                                    <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
                                        @if (count($all_locations) > 1)
                                            {!! Form::select('pending_shipments_location', $all_locations, null, [
                                                'class' => 'form-control select2 ',
                                                'placeholder' => __('lang_v1.select_location'),
                                                'id' => 'pending_shipments_location',
                                            ]) !!}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="tw-flow-root tw-mt-5  tw-border-gray-200">
                                <div class="tw--mx-4 tw--my-2 tw-overflow-x-auto sm:tw--mx-5">
                                    <div class="tw-inline-block tw-min-w-full tw-py-2 tw-align-middle sm:tw-px-5">
                                        <table class="table table-bordered table-striped ajax_view" id="shipments_table">
                                            <thead>
                                                <tr>
                                                    <th>@lang('messages.action')</th>
                                                    <th>@lang('messages.date')</th>
                                                    <th>@lang('sale.invoice_no')</th>
                                                    <th>@lang('sale.customer_name')</th>
                                                    <th>@lang('lang_v1.contact_no')</th>
                                                    <th>@lang('sale.location')</th>
                                                    <th>@lang('lang_v1.shipping_status')</th>
                                                    @if (!empty($custom_labels['shipping']['custom_field_1']))
                                                        <th>
                                                            {{ $custom_labels['shipping']['custom_field_1'] }}
                                                        </th>
                                                    @endif
                                                    @if (!empty($custom_labels['shipping']['custom_field_2']))
                                                        <th>
                                                            {{ $custom_labels['shipping']['custom_field_2'] }}
                                                        </th>
                                                    @endif
                                                    @if (!empty($custom_labels['shipping']['custom_field_3']))
                                                        <th>
                                                            {{ $custom_labels['shipping']['custom_field_3'] }}
                                                        </th>
                                                    @endif
                                                    @if (!empty($custom_labels['shipping']['custom_field_4']))
                                                        <th>
                                                            {{ $custom_labels['shipping']['custom_field_4'] }}
                                                        </th>
                                                    @endif
                                                    @if (!empty($custom_labels['shipping']['custom_field_5']))
                                                        <th>
                                                            {{ $custom_labels['shipping']['custom_field_5'] }}
                                                        </th>
                                                    @endif
                                                    <th>@lang('sale.payment_status')</th>
                                                    <th>@lang('restaurant.service_staff')</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                @if (auth()->user()->can('account.access') && config('constants.show_payments_recovered_today') == true)
                    <div
                        class="tw-transition-all lg:tw-col-span-2 tw-duration-200 tw-bg-white tw-shadow-sm tw-rounded-xl tw-ring-1 hover:tw-shadow-md hover:tw--translate-y-0.5 tw-ring-gray-200">
                        <div class="tw-p-4 sm:tw-p-5">
                            <div class="tw-flex tw-items-center tw-gap-2.5">
                                <div
                                    class="tw-border-2 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-w-10 tw-h-10">
                                    <svg aria-hidden="true" class="tw-text-yellow-500 tw-size-5 tw-shrink-0"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M12 9v4"></path>
                                        <path
                                            d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z">
                                        </path>
                                        <path d="M12 16h.01"></path>
                                    </svg>
                                </div>
                                <div class="tw-flex tw-items-center tw-flex-1 tw-min-w-0 tw-gap-1">
                                    <div class="tw-w-full sm:tw-w-1/2 md:tw-w-1/2">
                                        <h3 class="tw-font-bold tw-text-base lg:tw-text-xl">
                                            @lang('lang_v1.payment_recovered_today')
                                        </h3>
                                    </div>

                                </div>
                            </div>
                            <div class="tw-flow-root tw-mt-5  tw-border-gray-200">
                                <div class="tw--mx-4 tw--my-2 tw-overflow-x-auto sm:tw--mx-5">
                                    <div class="tw-inline-block tw-min-w-full tw-py-2 tw-align-middle sm:tw-px-5">
                                        <table class="table table-bordered table-striped" id="cash_flow_table">
                                            <thead>
                                                <tr>
                                                    <th>@lang('messages.date')</th>
                                                    <th>@lang('account.account')</th>
                                                    <th>@lang('lang_v1.description')</th>
                                                    <th>@lang('lang_v1.payment_method')</th>
                                                    <th>@lang('lang_v1.payment_details')</th>
                                                    <th>@lang('account.credit')</th>
                                                    <th>@lang('lang_v1.account_balance')
                                                        @show_tooltip(__('lang_v1.account_balance_tooltip'))</th>
                                                    <th>@lang('lang_v1.total_balance')
                                                        @show_tooltip(__('lang_v1.total_balance_tooltip'))</th>
                                                </tr>
                                            </thead>
                                            <tfoot>
                                                <tr class="bg-gray font-17 footer-total text-center">
                                                    <td colspan="5"><strong>@lang('sale.total'):</strong></td>
                                                    <td class="footer_total_credit"></td>
                                                    <td colspan="2"></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                {{-- @if (!empty($widgets['after_dashboard_reports']))
                    @foreach ($widgets['after_dashboard_reports'] as $widget)
                        {!! $widget !!}
                    @endforeach
                @endif --}}
            </div>
        </div>
    @endif

    </div>{{-- end pw-dashboard --}}

@endsection


<div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade edit_pso_status_modal" tabindex="-1" role="dialog"></div>
<div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>

@section('css')
    <style>
        .select2-container { width: 100% !important; }

        /* ── PrintWorks Dashboard (matches admin-pro purple theme) ── */
        .pw-dashboard {
            background: var(--admin-pro-bg, #f8f9fa);
        }

        /* Hero header */
        .pw-dash-hero {
            background: var(--admin-pro-hero-gradient, linear-gradient(135deg, #2d2a33 0%, #211f26 48%, #1a1820 100%));
            border-radius: 0 0 20px 20px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--admin-pro-hero-shadow, 0 6px 20px rgba(33, 31, 38, 0.22));
        }
        .pw-dash-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -8%;
            width: 360px;
            height: 360px;
            background: radial-gradient(circle, rgba(255,255,255,0.14) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .pw-dash-hero::after {
            content: '';
            position: absolute;
            bottom: -70%;
            left: 8%;
            width: 260px;
            height: 260px;
            background: radial-gradient(circle, rgba(255,255,255,0.07) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .pw-dash-eyebrow {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.75);
            margin: 0 0 6px;
        }
        .pw-dash-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #fff;
            margin: 0;
            line-height: 1.25;
            letter-spacing: -0.02em;
        }
        @media (min-width: 768px) {
            .pw-dash-title { font-size: 2rem; }
        }
        .pw-dash-subtitle {
            font-size: 14px;
            color: rgba(255,255,255,0.7);
            margin: 8px 0 0;
        }
        .pw-dash-date-btn {
            background: rgba(255,255,255,0.95);
            color: #211f26;
            border: none;
            box-shadow: 0 4px 14px rgba(33, 31, 38, 0.12);
        }
        .pw-dash-date-btn:hover {
            background: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .pw-dash-filter .select2-container--default .select2-selection--single {
            background: rgba(255,255,255,0.95);
            border: none;
            border-radius: 12px;
            height: 42px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.1);
        }
        .pw-dash-filter .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px;
            padding-left: 14px;
            color: #211f26;
            font-weight: 500;
        }

        /* Stat cards */
        .pw-dash-body {
            margin-top: -12px;
            position: relative;
            z-index: 2;
            padding-bottom: 4px;
        }
        .pw-stats-grid {
            position: relative;
            z-index: 2;
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        @media (min-width: 768px) {
            .pw-stats-grid { gap: 16px; }
        }
        @media (min-width: 1024px) {
            .pw-stats-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
        .pw-stat-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid rgba(33, 31, 38, 0.07);
            box-shadow: 0 2px 12px rgba(33, 31, 38, 0.05);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        .pw-stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 3px;
            height: 100%;
            background: linear-gradient(180deg, #3d3947, #211f26);
        }
        .pw-stat-card:hover {
            transform: translateY(-1px);
            border-color: rgba(33, 31, 38, 0.12);
            box-shadow: 0 6px 20px rgba(33, 31, 38, 0.08);
        }
        .pw-stat-card > div {
            padding: 14px 16px !important;
        }
        @media (min-width: 640px) {
            .pw-stat-card > div { padding: 16px 18px !important; }
        }
        .pw-stat-card .tw-flex {
            gap: 12px !important;
            align-items: center;
        }

        .pw-stat-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: rgba(33, 31, 38, 0.06);
            color: #5c5770;
        }
        .pw-stat-icon svg {
            width: 20px !important;
            height: 20px !important;
        }

        .pw-stat-label {
            font-size: 12px;
            font-weight: 500;
            color: #64748b;
            margin: 0;
            line-height: 1.35;
            white-space: normal;
        }
        .pw-stat-value {
            font-size: 1.125rem;
            font-weight: 700;
            color: #0f172a;
            margin: 3px 0 0;
            letter-spacing: -0.02em;
            font-variant-numeric: tabular-nums;
            line-height: 1.2;
        }

        /* Active production jobs */
        .pw-active-jobs {
            margin-top: 16px;
            margin-bottom: 4px;
        }
        .pw-jobs-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid rgba(33, 31, 38, 0.08);
            box-shadow: 0 2px 12px rgba(33, 31, 38, 0.05);
            overflow: hidden;
        }
        .pw-jobs-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px;
            border-bottom: 1px solid #f3f4f6;
            flex-wrap: wrap;
        }
        .pw-jobs-title {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
        }
        .pw-jobs-sub {
            font-size: 12px;
            color: #64748b;
            margin: 3px 0 0;
        }
        .pw-jobs-link {
            font-size: 12px;
            font-weight: 700;
            color: #211f26;
            text-decoration: none;
            padding: 7px 14px;
            border-radius: 8px;
            background: rgba(33, 31, 38, 0.06);
        }
        .pw-jobs-link:hover { background: rgba(33, 31, 38, 0.1); text-decoration: none; color: #211f26; }

        .pw-jobs-stages {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            padding: 14px 18px;
            background: #fafafa;
            border-bottom: 1px solid #f3f4f6;
        }
        @media (min-width: 640px) {
            .pw-jobs-stages { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        .pw-jobs-stage {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            text-decoration: none;
            color: inherit;
            transition: border-color .15s, box-shadow .15s;
        }
        .pw-jobs-stage:hover {
            border-color: rgba(33, 31, 38, 0.15);
            box-shadow: 0 2px 8px rgba(33, 31, 38, 0.06);
            text-decoration: none;
        }
        .pw-jobs-stage.has-jobs {
            border-color: rgba(33, 31, 38, 0.12);
            background: #fff;
        }
        .pw-jobs-stage-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .pw-jobs-stage-name {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            flex: 1;
            line-height: 1.2;
        }
        .pw-jobs-stage-count {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            min-width: 20px;
            text-align: right;
        }

        .pw-jobs-table-wrap { overflow-x: auto; }
        .pw-jobs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .pw-jobs-table th {
            text-align: left;
            padding: 10px 16px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #9ca3af;
            background: #fff;
            border-bottom: 1px solid #f3f4f6;
        }
        .pw-jobs-table td {
            padding: 11px 16px;
            border-bottom: 1px solid #f9fafb;
            color: #374151;
            vertical-align: middle;
        }
        .pw-jobs-table tr:last-child td { border-bottom: none; }
        .pw-jobs-table tr:hover td { background: #fafafa; }
        .pw-jobs-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }
        .pw-jobs-priority { font-size: 12px; font-weight: 700; }
        .pw-jobs-overdue { color: #ef4444 !important; font-weight: 700; }
        .pw-jobs-view {
            font-size: 12px;
            font-weight: 700;
            color: #211f26;
            text-decoration: none;
        }
        .pw-jobs-view:hover { text-decoration: underline; }
        .pw-jobs-empty {
            padding: 28px;
            text-align: center;
            color: #9ca3af;
            font-size: 13px;
        }

        /* Chart cards */
        .pw-dash-charts {
            padding-top: 12px !important;
        }
        .pw-chart-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(33, 31, 38, 0.08);
            box-shadow: var(--admin-pro-card-shadow, 0 4px 24px rgba(28, 28, 33, 0.06));
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .pw-chart-card:hover {
            border-color: rgba(33, 31, 38, 0.14);
            box-shadow: 0 8px 28px rgba(33, 31, 38, 0.08);
        }
        .pw-chart-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .pw-chart-head-icon {
            width: 40px; height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #3d3947, #211f26);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(33, 31, 38, 0.2);
        }
        .pw-chart-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.01em;
        }
        .pw-chart-body {
            background: linear-gradient(180deg, #f9f9fa 0%, #fff 100%);
            border-radius: 12px;
            padding: 14px;
            border: 1px solid rgba(33, 31, 38, 0.06);
        }
        .pw-chart-body p { margin: 0 !important; font-style: normal !important; color: inherit !important; }

        /* Lower section cards (tables) */
        .pw-dashboard .tw-ring-gray-200 {
            border-radius: 14px !important;
            border: 1px solid rgba(33, 31, 38, 0.08) !important;
            box-shadow: var(--admin-pro-card-shadow, 0 4px 24px rgba(28, 28, 33, 0.06)) !important;
        }
        .pw-dashboard .tw-ring-gray-200:hover {
            border-color: rgba(33, 31, 38, 0.14) !important;
            box-shadow: 0 8px 28px rgba(33, 31, 38, 0.08) !important;
        }
    </style>
@endsection

@section('javascript')
    <script src="{{ asset('js/home.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
    @includeIf('sales_order.common_js')
    @includeIf('purchase_order.common_js')
    @if (!empty($all_locations))
        {!! $sells_chart_1->script() !!}
        {!! $sells_chart_2->script() !!}
    @endif
    <script type="text/javascript">
        $(document).ready(function() {
            sales_order_table = $('#sales_order_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader:false,
                scrollY: "75vh",
                scrollX: true,
                scrollCollapse: true,
                aaSorting: [
                    [1, 'desc']
                ],
                "ajax": {
                    "url": '{{ action([\App\Http\Controllers\SellController::class, 'index']) }}?sale_type=sales_order',
                    "data": function(d) {
                        d.for_dashboard_sales_order = true;

                        if ($('#so_location').length > 0) {
                            d.location_id = $('#so_location').val();
                        }
                    }
                },
                columnDefs: [{
                    "targets": 7,
                    "orderable": false,
                    "searchable": false
                }],
                columns: [{
                        data: 'action',
                        name: 'action'
                    },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'invoice_no',
                        name: 'invoice_no'
                    },
                    {
                        data: 'conatct_name',
                        name: 'conatct_name'
                    },
                    {
                        data: 'mobile',
                        name: 'contacts.mobile'
                    },
                    {
                        data: 'business_location',
                        name: 'bl.name'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'shipping_status',
                        name: 'shipping_status'
                    },
                    {
                        data: 'so_qty_remaining',
                        name: 'so_qty_remaining',
                        "searchable": false
                    },
                    {
                        data: 'added_by',
                        name: 'u.first_name'
                    },
                ]
            });

            @if (auth()->user()->can('account.access') && config('constants.show_payments_recovered_today') == true)

                // Cash Flow Table
                cash_flow_table = $('#cash_flow_table').DataTable({
                    processing: true,
                    serverSide: true,
                    fixedHeader:false,
                    "ajax": {
                        "url": "{{ action([\App\Http\Controllers\AccountController::class, 'cashFlow']) }}",
                        "data": function(d) {
                            d.type = 'credit';
                            d.only_payment_recovered = true;
                        }
                    },
                    "ordering": false,
                    "searching": false,
                    columns: [{
                            data: 'operation_date',
                            name: 'operation_date'
                        },
                        {
                            data: 'account_name',
                            name: 'account_name'
                        },
                        {
                            data: 'sub_type',
                            name: 'sub_type'
                        },
                        {
                            data: 'method',
                            name: 'TP.method'
                        },
                        {
                            data: 'payment_details',
                            name: 'payment_details',
                            searchable: false
                        },
                        {
                            data: 'credit',
                            name: 'amount'
                        },
                        {
                            data: 'balance',
                            name: 'balance'
                        },
                        {
                            data: 'total_balance',
                            name: 'total_balance'
                        },
                    ],
                    "fnDrawCallback": function(oSettings) {
                        __currency_convert_recursively($('#cash_flow_table'));
                    },
                    "footerCallback": function(row, data, start, end, display) {
                        var footer_total_credit = 0;

                        for (var r in data) {
                            footer_total_credit += $(data[r].credit).data('orig-value') ? parseFloat($(
                                data[r].credit).data('orig-value')) : 0;
                        }
                        $('.footer_total_credit').html(__currency_trans_from_en(footer_total_credit));
                    }
                });
            @endif

            $('#so_location').change(function() {
                sales_order_table.ajax.reload();
            });
            @if (!empty($common_settings['enable_purchase_order']))
                //Purchase table
                purchase_order_table = $('#purchase_order_table').DataTable({
                    processing: true,
                    serverSide: true,
                    fixedHeader:false,
                    aaSorting: [
                        [1, 'desc']
                    ],
                    scrollY: "75vh",
                    scrollX: true,
                    scrollCollapse: true,
                    ajax: {
                        url: '{{ action([\App\Http\Controllers\PurchaseOrderController::class, 'index']) }}',
                        data: function(d) {
                            d.from_dashboard = true;

                            if ($('#po_location').length > 0) {
                                d.location_id = $('#po_location').val();
                            }
                        },
                    },
                    columns: [{
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'transaction_date',
                            name: 'transaction_date'
                        },
                        {
                            data: 'ref_no',
                            name: 'ref_no'
                        },
                        {
                            data: 'location_name',
                            name: 'BS.name'
                        },
                        {
                            data: 'name',
                            name: 'contacts.name'
                        },
                        {
                            data: 'status',
                            name: 'transactions.status'
                        },
                        {
                            data: 'po_qty_remaining',
                            name: 'po_qty_remaining',
                            "searchable": false
                        },
                        {
                            data: 'added_by',
                            name: 'u.first_name'
                        }
                    ]
                })

                $('#po_location').change(function() {
                    purchase_order_table.ajax.reload();
                });
            @endif

            @if (!empty($common_settings['enable_purchase_requisition']))
                //Purchase table
                purchase_requisition_table = $('#purchase_requisition_table').DataTable({
                    processing: true,
                    serverSide: true,
                    fixedHeader:false,
                    aaSorting: [
                        [1, 'desc']
                    ],
                    scrollY: "75vh",
                    scrollX: true,
                    scrollCollapse: true,
                    ajax: {
                        url: '{{ action([\App\Http\Controllers\PurchaseRequisitionController::class, 'index']) }}',
                        data: function(d) {
                            d.from_dashboard = true;

                            if ($('#pr_location').length > 0) {
                                d.location_id = $('#pr_location').val();
                            }
                        },
                    },
                    columns: [{
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'transaction_date',
                            name: 'transaction_date'
                        },
                        {
                            data: 'ref_no',
                            name: 'ref_no'
                        },
                        {
                            data: 'location_name',
                            name: 'BS.name'
                        },
                        {
                            data: 'status',
                            name: 'status'
                        },
                        {
                            data: 'delivery_date',
                            name: 'delivery_date'
                        },
                        {
                            data: 'added_by',
                            name: 'u.first_name'
                        },
                    ]
                })

                $('#pr_location').change(function() {
                    purchase_requisition_table.ajax.reload();
                });

                $(document).on('click', 'a.delete-purchase-requisition', function(e) {
                    e.preventDefault();
                    swal({
                        title: LANG.sure,
                        icon: 'warning',
                        buttons: true,
                        dangerMode: true,
                    }).then(willDelete => {
                        if (willDelete) {
                            var href = $(this).attr('href');
                            $.ajax({
                                method: 'DELETE',
                                url: href,
                                dataType: 'json',
                                success: function(result) {
                                    if (result.success == true) {
                                        toastr.success(result.msg);
                                        purchase_requisition_table.ajax.reload();
                                    } else {
                                        toastr.error(result.msg);
                                    }
                                },
                            });
                        }
                    });
                });
            @endif

            sell_table = $('#shipments_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader:false,
                aaSorting: [
                    [1, 'desc']
                ],
                scrollY: "75vh",
                scrollX: true,
                scrollCollapse: true,
                "ajax": {
                    "url": '{{ action([\App\Http\Controllers\SellController::class, 'index']) }}',
                    "data": function(d) {
                        d.only_pending_shipments = true;
                        if ($('#pending_shipments_location').length > 0) {
                            d.location_id = $('#pending_shipments_location').val();
                        }
                    }
                },
                columns: [{
                        data: 'action',
                        name: 'action',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'invoice_no',
                        name: 'invoice_no'
                    },
                    {
                        data: 'conatct_name',
                        name: 'conatct_name'
                    },
                    {
                        data: 'mobile',
                        name: 'contacts.mobile'
                    },
                    {
                        data: 'business_location',
                        name: 'bl.name'
                    },
                    {
                        data: 'shipping_status',
                        name: 'shipping_status'
                    },
                    @if (!empty($custom_labels['shipping']['custom_field_1']))
                        {
                            data: 'shipping_custom_field_1',
                            name: 'shipping_custom_field_1'
                        },
                    @endif
                    @if (!empty($custom_labels['shipping']['custom_field_2']))
                        {
                            data: 'shipping_custom_field_2',
                            name: 'shipping_custom_field_2'
                        },
                    @endif
                    @if (!empty($custom_labels['shipping']['custom_field_3']))
                        {
                            data: 'shipping_custom_field_3',
                            name: 'shipping_custom_field_3'
                        },
                    @endif
                    @if (!empty($custom_labels['shipping']['custom_field_4']))
                        {
                            data: 'shipping_custom_field_4',
                            name: 'shipping_custom_field_4'
                        },
                    @endif
                    @if (!empty($custom_labels['shipping']['custom_field_5']))
                        {
                            data: 'shipping_custom_field_5',
                            name: 'shipping_custom_field_5'
                        },
                    @endif {
                        data: 'payment_status',
                        name: 'payment_status'
                    },
                    {
                        data: 'waiter',
                        name: 'ss.first_name',
                        @if (empty($is_service_staff_enabled))
                            visible: false
                        @endif
                    }
                ],
                "fnDrawCallback": function(oSettings) {
                    __currency_convert_recursively($('#sell_table'));
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).find('td:eq(4)').attr('class', 'clickable_td');
                }
            });

            $('#pending_shipments_location').change(function() {
                sell_table.ajax.reload();
            });
        });
    </script>
    
@endsection
