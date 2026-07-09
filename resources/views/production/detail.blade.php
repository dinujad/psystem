@extends('layouts.app')
@section('title', $job->job_number . ' — Cost Detail')

@section('css')
<style>
.det-page  { padding: 0 20px 60px; max-width: 1100px; margin: 0 auto; }
.det-back  { display: inline-flex; align-items: center; gap: 6px; color: #7c5cfc; font-size: 13px; font-weight: 600; text-decoration: none; margin: 18px 0 14px; }
.det-back:hover { text-decoration: none; color: #5b3fd9; }

/* Hero */
.det-hero  { background: linear-gradient(135deg, #1e1b4b, #312e81 60%, #4f46e5); border-radius: 18px; padding: 26px 30px; color: #fff; margin-bottom: 22px; }
.det-hero-row { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; }
.det-hero-num  { font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; opacity: .7; }
.det-hero-title{ font-size: 22px; font-weight: 800; margin: 4px 0 6px; }
.det-hero-cust { font-size: 14px; opacity: .8; }

/* Cost summary badges */
.det-cost-badges { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px; }
.det-cost-badge  { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2); border-radius: 12px; padding: 12px 18px; }
.det-cost-badge-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; opacity: .7; margin-bottom: 3px; }
.det-cost-badge-val   { font-size: 20px; font-weight: 900; }
.det-cost-badge.grand { background: rgba(16,185,129,.2); border-color: rgba(16,185,129,.4); }
.det-cost-badge.grand .det-cost-badge-val { color: #6ee7b7; }

/* Grid */
.det-grid  { display: grid; grid-template-columns: 1fr 380px; gap: 18px; align-items: start; }
@media (max-width: 860px) { .det-grid { grid-template-columns: 1fr; } }

/* Cards */
.det-card  { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; margin-bottom: 18px; }
.det-card-head { padding: 13px 18px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; gap: 10px; }
.det-card-head h3 { font-size: 14px; font-weight: 800; color: #111827; margin: 0; }
.det-card-icon { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }

/* Stage timeline */
.det-stages    { padding: 16px; }
.det-stage-row { border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px 16px; margin-bottom: 12px; position: relative; overflow: hidden; }
.det-stage-row::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; }
.det-stage-hdr  { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.det-stage-name { font-size: 14px; font-weight: 800; }
.det-stage-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.det-stage-meta  { font-size: 11px; color: #9ca3af; }

.det-stage-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px; }
@media (max-width: 600px) { .det-stage-grid { grid-template-columns: 1fr 1fr; } }
.det-stage-stat { background: #f9fafb; border-radius: 8px; padding: 8px 10px; }
.det-stat-label { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: .05em; }
.det-stat-val   { font-size: 14px; font-weight: 800; color: #111827; margin-top: 2px; }
.det-stat-val.green { color: #10b981; }
.det-stat-val.purple { color: #7c5cfc; }
.det-stat-val.amber  { color: #f59e0b; }

/* QC Stars */
.det-stars { font-size: 18px; }
.det-star-filled { color: #f59e0b; }
.det-star-empty  { color: #d1d5db; }

/* Materials table */
.det-mat-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.det-mat-table th { padding: 10px 14px; background: #f9fafb; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #9ca3af; text-align: left; }
.det-mat-table td { padding: 10px 14px; border-bottom: 1px solid #f3f4f6; color: #374151; }
.det-mat-table tr:last-child td { border-bottom: none; }
.det-mat-total { text-align: right; padding: 10px 14px; font-weight: 800; font-size: 14px; background: #f9fafb; }

/* Summary breakdown */
.det-summary-row { display: flex; justify-content: space-between; padding: 9px 16px; font-size: 13px; border-bottom: 1px solid #f3f4f6; }
.det-summary-row:last-child { border-bottom: none; }
.det-summary-label { color: #6b7280; }
.det-summary-val { font-weight: 700; color: #111827; }
.det-summary-total { background: #f5f3ff; }
.det-summary-total .det-summary-label { color: #7c5cfc; font-weight: 800; }
.det-summary-total .det-summary-val { color: #7c5cfc; font-size: 16px; font-weight: 900; }
</style>
@endsection

@section('content')
<div class="det-page">
    <a href="{{ route('production.show', $job) }}" class="det-back">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
        Back to Job
    </a>

    {{-- Hero --}}
    <div class="det-hero">
        <div class="det-hero-row">
            <div>
                <div class="det-hero-num">{{ $job->job_number }} — Cost & Timeline Detail</div>
                <div class="det-hero-title">{{ $job->title }}</div>
                <div class="det-hero-cust">👤 {{ $job->customer_name }}
                    @if($job->customer_phone) · {{ $job->customer_phone }} @endif
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;align-items:flex-end;">
                <span style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);border-radius:20px;padding:5px 14px;font-size:12px;font-weight:700;">
                    {{ \App\ProductionJob::allStages()[$job->current_stage] ?? $job->current_stage }}
                </span>
                <span style="font-size:11px;opacity:.6;">Created {{ $job->created_at->format('d M Y') }}</span>
            </div>
        </div>

        <div class="det-cost-badges">
            @foreach($job->stageHistory as $sh)
            @if($sh->stage_rate)
            <div class="det-cost-badge">
                <div class="det-cost-badge-label">{{ $stages[$sh->stage] ?? $sh->stage }}</div>
                <div class="det-cost-badge-val">Rs {{ number_format($sh->stage_rate, 2) }}</div>
            </div>
            @endif
            @endforeach
            @if($materialCost > 0)
            <div class="det-cost-badge">
                <div class="det-cost-badge-label">Materials</div>
                <div class="det-cost-badge-val">Rs {{ number_format($materialCost, 2) }}</div>
            </div>
            @endif
            <div class="det-cost-badge grand">
                <div class="det-cost-badge-label">Grand Total</div>
                <div class="det-cost-badge-val">Rs {{ number_format($grandTotal, 2) }}</div>
            </div>
            @if($jobRevenue > 0)
            <div class="det-cost-badge">
                <div class="det-cost-badge-label">Revenue</div>
                <div class="det-cost-badge-val">Rs {{ number_format($jobRevenue, 2) }}</div>
            </div>
            <div class="det-cost-badge" style="background:{{ $jobProfit >= 0 ? 'rgba(16,185,129,.2)' : 'rgba(239,68,68,.2)' }};border-color:{{ $jobProfit >= 0 ? 'rgba(16,185,129,.4)' : 'rgba(239,68,68,.4)' }};">
                <div class="det-cost-badge-label">Profit / Loss</div>
                <div class="det-cost-badge-val" style="color:{{ $jobProfit >= 0 ? '#6ee7b7' : '#fca5a5' }};">Rs {{ number_format($jobProfit, 2) }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="det-grid">

        {{-- LEFT: Stage timeline --}}
        <div>
            <div class="det-card">
                <div class="det-card-head">
                    <div class="det-card-icon" style="background:#ede9fe;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7c5cfc" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    </div>
                    <h3>Stage Timeline & Costs</h3>
                </div>
                <div class="det-stages">
                    @forelse($job->stageHistory->sortBy('started_at') as $sh)
                    @php
                        $color    = \App\ProductionJob::stageColor($sh->stage);
                        $durMin   = $sh->task_duration;
                        $durStr   = $durMin !== null
                            ? ($durMin >= 60 ? floor($durMin/60).'h '.($durMin%60).'m' : $durMin.'m')
                            : null;
                        $waitMin  = $sh->started_at && $sh->task_started_at
                            ? (int)$sh->started_at->diffInMinutes($sh->task_started_at)
                            : null;
                    @endphp
                    <div class="det-stage-row" style="border-left: 4px solid {{ $color }};">
                        <div class="det-stage-hdr">
                            <div class="det-stage-badge" style="background:{{ $color }}22;color:{{ $color }};">{{ $stages[$sh->stage] ?? $sh->stage }}</div>
                            @if($sh->completed_at)
                            <span style="font-size:11px;color:#10b981;font-weight:700;">✓ Done</span>
                            @elseif($sh->task_started_at)
                            <span style="font-size:11px;color:#f59e0b;font-weight:700;">In Progress</span>
                            @else
                            <span style="font-size:11px;color:#9ca3af;font-weight:700;">Pending</span>
                            @endif
                            @if($sh->stage_rate)
                            <span style="margin-left:auto;font-size:14px;font-weight:800;color:#7c5cfc;">Rs {{ number_format($sh->stage_rate, 2) }}</span>
                            @endif
                        </div>

                        <div class="det-stage-meta">
                            Assigned to: <strong>{{ $sh->movedBy->name ?? '—' }}</strong>
                            @if($sh->notes) · {{ $sh->notes }} @endif
                        </div>

                        <div class="det-stage-grid">
                            <div class="det-stage-stat">
                                <div class="det-stat-label">Stage Started</div>
                                <div class="det-stat-val">{{ $sh->started_at ? $sh->started_at->format('d M, h:i A') : '—' }}</div>
                            </div>
                            <div class="det-stage-stat">
                                <div class="det-stat-label">Task Started</div>
                                <div class="det-stat-val">{{ $sh->task_started_at ? $sh->task_started_at->format('d M, h:i A') : '—' }}</div>
                            </div>
                            <div class="det-stage-stat">
                                <div class="det-stat-label">Task Ended</div>
                                <div class="det-stat-val">{{ $sh->task_ended_at ? $sh->task_ended_at->format('d M, h:i A') : '—' }}</div>
                            </div>
                            @if($durStr)
                            <div class="det-stage-stat">
                                <div class="det-stat-label">Work Duration</div>
                                <div class="det-stat-val green">{{ $durStr }}</div>
                            </div>
                            @endif
                            @if($sh->stage_rate)
                            <div class="det-stage-stat">
                                <div class="det-stat-label">Stage Rate</div>
                                <div class="det-stat-val purple">Rs {{ number_format($sh->stage_rate, 2) }}</div>
                            </div>
                            @endif
                            @if($sh->stage_rate_notes)
                            <div class="det-stage-stat" style="grid-column: span 2;">
                                <div class="det-stat-label">Rate Notes</div>
                                <div class="det-stat-val" style="font-size:12px;font-weight:400;color:#6b7280;">{{ $sh->stage_rate_notes }}</div>
                            </div>
                            @endif
                        </div>

                        {{-- QC quality rating on production stage --}}
                        @if($sh->stage === 'production' && $sh->quality_rating)
                        <div style="margin-top:10px;padding:10px 12px;background:#fef9c3;border-radius:9px;border:1px solid #fde68a;">
                            <div style="font-size:11px;font-weight:700;color:#92400e;margin-bottom:5px;">⭐ QC Rating for this stage</div>
                            <div style="font-size:20px;margin-bottom:4px;">
                                @for($i = 1; $i <= 5; $i++)
                                <span class="{{ $i <= $sh->quality_rating ? 'det-star-filled' : 'det-star-empty' }}">★</span>
                                @endfor
                                <strong style="font-size:14px;vertical-align:middle;margin-left:6px;color:#111827;">{{ $sh->quality_rating }}/5</strong>
                            </div>
                            @if($sh->quality_comment)
                            <div style="font-size:13px;color:#374151;font-style:italic;">"{{ $sh->quality_comment }}"</div>
                            @endif
                        </div>
                        @endif
                    </div>
                    @empty
                    <div style="text-align:center;padding:30px;color:#d1d5db;">No stage history yet.</div>
                    @endforelse
                </div>
            </div>

            {{-- Materials table --}}
            @if($job->materials->count() > 0)
            <div class="det-card">
                <div class="det-card-head">
                    <div class="det-card-icon" style="background:#fef3c7;">📦</div>
                    <h3>Raw Materials Used</h3>
                    <span style="margin-left:auto;font-weight:800;color:#f59e0b;">Rs {{ number_format($materialCost, 2) }}</span>
                </div>
                <table class="det-mat-table">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th style="text-align:right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($job->materials as $mat)
                        <tr>
                            <td style="font-weight:700;">{{ $mat->material->name ?? '—' }}</td>
                            <td style="color:#9ca3af;">{{ $mat->material->category?->name ?? '—' }}</td>
                            <td>{{ $mat->quantity }} {{ $mat->material->unit?->abbreviation }}</td>
                            <td>Rs {{ number_format($mat->unit_price, 2) }}</td>
                            <td style="text-align:right;font-weight:700;color:#111827;">Rs {{ number_format($mat->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="det-mat-total">Total Materials Cost</td>
                            <td class="det-mat-total" style="color:#f59e0b;">Rs {{ number_format($materialCost, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </div>

        {{-- RIGHT: Summary + info --}}
        <div>

            {{-- Cost Summary --}}
            <div class="det-card">
                <div class="det-card-head">
                    <div class="det-card-icon" style="background:#d1fae5;">💰</div>
                    <h3>Cost Summary</h3>
                </div>
                <div>
                    @foreach($job->stageHistory->sortBy('started_at') as $sh)
                    <div class="det-summary-row">
                        <span class="det-summary-label">{{ $stages[$sh->stage] ?? $sh->stage }}</span>
                        <span class="det-summary-val">{{ $sh->stage_rate ? 'Rs '.number_format($sh->stage_rate, 2) : '—' }}</span>
                    </div>
                    @endforeach
                    <div class="det-summary-row">
                        <span class="det-summary-label">Materials</span>
                        <span class="det-summary-val">{{ $materialCost > 0 ? 'Rs '.number_format($materialCost, 2) : '—' }}</span>
                    </div>
                    <div class="det-summary-row det-summary-total">
                        <span class="det-summary-label">Grand Total</span>
                        <span class="det-summary-val">Rs {{ number_format($grandTotal, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Job Info --}}
            <div class="det-card">
                <div class="det-card-head">
                    <div class="det-card-icon" style="background:#ede9fe;">📋</div>
                    <h3>Job Info</h3>
                </div>
                <div style="padding:14px 18px;">
                    <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;">
                        <div style="display:flex;gap:8px;"><span style="color:#9ca3af;min-width:100px;">Job #</span><strong>{{ $job->job_number }}</strong></div>
                        <div style="display:flex;gap:8px;"><span style="color:#9ca3af;min-width:100px;">Customer</span><span>{{ $job->customer_name }}</span></div>
                        @if($job->customer_phone)
                        <div style="display:flex;gap:8px;"><span style="color:#9ca3af;min-width:100px;">Phone</span><span>{{ $job->customer_phone }}</span></div>
                        @endif
                        <div style="display:flex;gap:8px;"><span style="color:#9ca3af;min-width:100px;">Priority</span>
                            <span style="background:{{ \App\ProductionJob::priorityColor($job->priority) }}22;color:{{ \App\ProductionJob::priorityColor($job->priority) }};padding:1px 9px;border-radius:20px;font-size:11px;font-weight:700;">{{ ucfirst($job->priority) }}</span>
                        </div>
                        @if($job->due_date)
                        <div style="display:flex;gap:8px;"><span style="color:#9ca3af;min-width:100px;">Due Date</span><span {{ $job->due_date->isPast() ? 'style="color:#ef4444;font-weight:700;"' : '' }}>{{ $job->due_date->format('d M Y') }}</span></div>
                        @endif
                        <div style="display:flex;gap:8px;"><span style="color:#9ca3af;min-width:100px;">Created By</span><span>{{ $job->creator->name ?? '—' }}</span></div>
                        <div style="display:flex;gap:8px;"><span style="color:#9ca3af;min-width:100px;">Created</span><span>{{ $job->created_at->format('d M Y') }}</span></div>
                        @if($job->google_drive_url)
                        <div style="display:flex;gap:8px;"><span style="color:#9ca3af;min-width:100px;">Drive</span>
                            <a href="{{ $job->google_drive_url }}" target="_blank" style="color:#7c5cfc;text-decoration:none;font-weight:600;">Open ↗</a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Linked inquiry --}}
            @if($job->inquiry)
            <div class="det-card">
                <div class="det-card-head">
                    <div class="det-card-icon" style="background:#dcfce7;">💬</div>
                    <h3>WhatsApp Inquiry</h3>
                </div>
                <div style="padding:14px 18px;display:flex;flex-direction:column;gap:8px;font-size:13px;">
                    <div style="display:flex;gap:8px;"><span style="color:#9ca3af;min-width:90px;">Category</span><span>{{ $job->inquiry->inquiry_category ?? '—' }}</span></div>
                    <div style="display:flex;gap:8px;"><span style="color:#9ca3af;min-width:90px;">Agent</span><span>{{ $job->inquiry->agent->name ?? '—' }}</span></div>
                    <div style="margin-top:6px;display:flex;gap:7px;">
                        <a href="{{ route('admin.whatsapp.inquiries.show', $job->inquiry_id) }}" style="background:#ede9fe;color:#7c5cfc;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;">View Inquiry</a>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
