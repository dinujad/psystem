@extends('layouts.app')
@section('title', 'Task View')

@section('css')
<style>
.tv-page{padding:0 20px 60px;max-width:1400px;margin:0 auto}
.tv-head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin:20px 0 16px}
.tv-title{font-size:20px;font-weight:800;color:#1e1b4b}
.tv-week{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.tv-pill{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:8px 14px;font-size:13px;font-weight:700}
.tv-btn{background:#7c5cfc;color:#fff;border:none;border-radius:9px;padding:8px 14px;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.tv-btn.outline{background:#fff;color:#7c5cfc;border:1.5px solid #7c5cfc}
.tv-tabs{display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap}
.tv-tab{padding:8px 14px;border-radius:999px;font-size:12px;font-weight:800;text-decoration:none;background:#f3f4f6;color:#374151}
.tv-tab.active{background:#7c5cfc;color:#fff}
.tv-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin-bottom:16px}
.tv-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px}
.tv-card .n{font-size:22px;font-weight:800;color:#1e1b4b}
.tv-card .l{font-size:11px;color:#6b7280;font-weight:700;text-transform:uppercase}
.tv-panel{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;margin-bottom:16px}
.tv-emp{border:1px solid #e5e7eb;border-radius:12px;padding:12px;margin-bottom:10px}
.tv-emp-head{display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;align-items:center;margin-bottom:8px}
.tv-emp-name{font-weight:800;color:#111827}
.tv-status{font-size:11px;font-weight:800;padding:4px 10px;border-radius:999px}
.tv-status.green{background:#dcfce7;color:#15803d}
.tv-status.yellow{background:#fef9c3;color:#a16207}
.tv-status.red{background:#fee2e2;color:#dc2626}
.tv-badges{display:flex;gap:6px;flex-wrap:wrap;font-size:11px;font-weight:700}
.tv-badges span{background:#f3f4f6;padding:3px 8px;border-radius:999px}
.tv-tasks{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px}
.tv-task{background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:8px;font-size:11px}
.tv-task.overdue{background:#fef2f2;border-color:#fca5a5}
.tv-task.completed{background:#f0fdf4;border-color:#86efac}
.tv-task.in_progress{background:#eff6ff;border-color:#93c5fd}
.tv-task strong{display:block;margin-bottom:4px;color:#111827}
.tv-rank{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f3f4f6}
.tv-rank:last-child{border-bottom:none}
.tv-rank-num{width:32px;height:32px;border-radius:50%;background:#ede9fe;color:#5b21b6;display:flex;align-items:center;justify-content:center;font-weight:800}
.tv-rank-num.top{background:#fef3c7;color:#b45309}
.tv-chart-wrap{position:relative;height:320px}
.tv-emp-select{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:14px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px}
.tv-emp-select label{font-size:12px;font-weight:800;color:#374151}
.tv-emp-select select{border:1px solid #d1d5db;border-radius:8px;padding:8px 12px;font-size:13px;min-width:240px;background:#fff}
.tv-trend{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:800;padding:8px 12px;border-radius:999px}
.tv-trend.up{background:#dcfce7;color:#15803d}
.tv-trend.down{background:#fee2e2;color:#dc2626}
.tv-trend.same{background:#f3f4f6;color:#4b5563}
.tv-trend.none{background:#eff6ff;color:#1d4ed8}
.tv-compare{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:14px}
.tv-compare .tv-card .sub{font-size:11px;color:#6b7280;margin-top:4px}
.tv-table{width:100%;border-collapse:collapse;font-size:12px}
.tv-table th{text-align:left;padding:8px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-size:11px;text-transform:uppercase;color:#6b7280}
.tv-table td{padding:8px;border-bottom:1px solid #f3f4f6}
.tv-table tr.current{background:#f5f3ff}
</style>
@endsection

@section('content')
<div class="tv-page">
    <div class="tv-head">
        <div class="tv-title"><i class="fas fa-chart-line" style="color:#7c5cfc;"></i> Task View</div>
        <div class="tv-week">
            <a class="tv-btn outline" href="{{ route('employee-todos.task-view', array_filter(['week' => $prevWeek, 'tab' => $tab, 'employee' => $tab === 'employee' ? ($selectedEmployeeId ?? null) : null])) }}"><i class="fas fa-chevron-left"></i> Prev</a>
            <span class="tv-pill">{{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M Y') }}</span>
            <a class="tv-btn outline" href="{{ route('employee-todos.task-view', array_filter(['week' => $nextWeek, 'tab' => $tab, 'employee' => $tab === 'employee' ? ($selectedEmployeeId ?? null) : null])) }}">Next <i class="fas fa-chevron-right"></i></a>
            <a class="tv-btn outline" href="{{ route('employee-todos.task-view', array_filter(['week' => now()->startOfWeek()->toDateString(), 'tab' => $tab, 'employee' => $tab === 'employee' ? ($selectedEmployeeId ?? null) : null])) }}">This Week</a>
        </div>
    </div>

    <div class="tv-tabs">
        <a class="tv-tab {{ $tab === 'overview' ? 'active' : '' }}" href="{{ route('employee-todos.task-view', ['week' => $weekStart->toDateString(), 'tab' => 'overview']) }}">Overview</a>
        <a class="tv-tab {{ $tab === 'performance' ? 'active' : '' }}" href="{{ route('employee-todos.task-view', ['week' => $weekStart->toDateString(), 'tab' => 'performance']) }}">Performance</a>
        <a class="tv-tab {{ $tab === 'employee' ? 'active' : '' }}" href="{{ route('employee-todos.task-view', ['week' => $weekStart->toDateString(), 'tab' => 'employee', 'employee' => $selectedEmployeeId ?? null]) }}">Employee Detail</a>
        <a class="tv-tab {{ $tab === 'charts' ? 'active' : '' }}" href="{{ route('employee-todos.task-view', ['week' => $weekStart->toDateString(), 'tab' => 'charts']) }}">Charts</a>
    </div>

    @if($tab === 'overview')
    <div class="tv-cards">
        <div class="tv-card"><div class="n">{{ $overview['working'] }}</div><div class="l">Working now</div></div>
        <div class="tv-card"><div class="n" style="color:#dc2626;">{{ $overview['overdue'] }}</div><div class="l">With overdue</div></div>
        <div class="tv-card"><div class="n" style="color:#15803d;">{{ $overview['completed'] }}</div><div class="l">Completed tasks</div></div>
        <div class="tv-card"><div class="n">{{ $overview['pending'] }}</div><div class="l">Still open</div></div>
    </div>

    <div class="tv-panel">
        @forelse($rows as $row)
        <div class="tv-emp">
            <div class="tv-emp-head">
                <div>
                    <span class="tv-emp-name">{{ $row['employee']['name'] }}</span>
                    <span class="tv-status {{ $row['status']['color'] }}">{{ $row['status']['label'] }}</span>
                </div>
                <div class="tv-badges">
                    <span>Today {{ $row['today_done'] }}/{{ $row['today_total'] }}</span>
                    <span>In progress {{ $row['in_progress'] }}</span>
                    <span>⭐ {{ $row['badges']['stars'] }}</span>
                    <span>Super {{ $row['badges']['super'] }}</span>
                    <span>Great {{ $row['badges']['great'] }}</span>
                    <span>Overdue {{ $row['badges']['overdue'] }}</span>
                </div>
            </div>
            @if(!empty($row['tasks']))
            <div class="tv-tasks">
                @foreach($row['tasks'] as $t)
                <div class="tv-task {{ $t['status'] }}">
                    <strong>{{ $t['title'] }}</strong>
                    <div>{{ $t['category_name'] ?? '—' }} · Day {{ $t['day_of_week'] }}</div>
                    <div>
                        Budget {{ $t['allocated_minutes'] }}m
                        {{ !empty($t['started_at']) ? '· Start '.$t['started_at'] : '' }}
                        {{ !empty($t['ended_at']) ? '· End '.$t['ended_at'] : '' }}
                        {{ !empty($t['actual_minutes']) ? '· Took '.$t['actual_minutes'].'m' : '' }}
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div style="font-size:12px;color:#9ca3af;">No tasks this week.</div>
            @endif
        </div>
        @empty
        <div style="color:#9ca3af;padding:20px;text-align:center;">No employees found.</div>
        @endforelse
    </div>
    @endif

    @if($tab === 'performance')
    <div class="tv-panel">
        <h3 style="margin:0 0 12px;font-size:15px;font-weight:800;">Week rankings</h3>
        @forelse(($rankedRows ?? []) as $row)
        <div class="tv-rank">
            <div class="tv-rank-num {{ $row['rank'] <= 3 ? 'top' : '' }}">{{ $row['rank'] }}</div>
            <div style="flex:1;">
                <div style="font-weight:800;">{{ $row['employee']['name'] }}</div>
                <div style="font-size:12px;color:#6b7280;">
                    Score {{ $row['score'] }} · ⭐ {{ $row['badges']['stars'] }} · Super {{ $row['badges']['super'] }} · Great {{ $row['badges']['great'] }} · Done {{ $row['badges']['done'] }}/{{ $row['badges']['total'] }}
                    {{ $row['avg_ratio'] !== null ? '· Avg time ratio '.$row['avg_ratio'].'x' : '' }}
                </div>
            </div>
            <span class="tv-status {{ $row['status']['color'] }}">{{ $row['status']['label'] }}</span>
        </div>
        @empty
        <div style="color:#9ca3af;padding:24px;text-align:center;font-size:13px;">
            No rankings yet. Ranks appear after employees start completing tasks this week.
        </div>
        @endforelse
    </div>
    @endif

    @if($tab === 'employee')
    @php
        $trend = $employeeTrend ?? [];
        $trendKey = $trend['trend']['key'] ?? 'none';
        $current = $trend['current'] ?? null;
        $previous = $trend['previous'] ?? null;
    @endphp
    <form method="GET" action="{{ route('employee-todos.task-view') }}" class="tv-emp-select">
        <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
        <input type="hidden" name="tab" value="employee">
        <label for="tvEmployee">Employee</label>
        <select id="tvEmployee" name="employee" onchange="this.form.submit()">
            @foreach(($employees ?? collect()) as $emp)
            <option value="{{ $emp['id'] }}" @selected((string)($selectedEmployeeId ?? '') === (string)$emp['id'])>{{ $emp['name'] }}</option>
            @endforeach
        </select>
        <span style="font-size:12px;color:#6b7280;">Compare this week with previous weeks for the selected person.</span>
    </form>

    @if(!empty($trend['employee']))
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:12px;">
        <div style="font-size:16px;font-weight:800;color:#1e1b4b;">{{ $trend['employee']['name'] }}</div>
        <span class="tv-trend {{ $trendKey }}">{{ $trend['trend']['label'] ?? 'No data yet' }}</span>
    </div>

    <div class="tv-compare">
        <div class="tv-card">
            <div class="n">{{ $current['score'] ?? 0 }}</div>
            <div class="l">This week score</div>
            <div class="sub">
                @if($previous)
                    {{ ($trend['trend']['delta_score'] ?? 0) >= 0 ? '+' : '' }}{{ $trend['trend']['delta_score'] ?? 0 }} vs last week
                @else
                    No previous week data
                @endif
            </div>
        </div>
        <div class="tv-card">
            <div class="n">{{ $current['badges']['done'] ?? 0 }}/{{ $current['badges']['total'] ?? 0 }}</div>
            <div class="l">Done this week</div>
            <div class="sub">
                @if($previous)
                    {{ ($trend['trend']['delta_done'] ?? 0) >= 0 ? '+' : '' }}{{ $trend['trend']['delta_done'] ?? 0 }} tasks vs last week
                @else
                    —
                @endif
            </div>
        </div>
        <div class="tv-card">
            <div class="n">⭐ {{ $current['badges']['stars'] ?? 0 }}</div>
            <div class="l">Stars</div>
            <div class="sub">Last week: {{ $previous['badges']['stars'] ?? 0 }}</div>
        </div>
        <div class="tv-card">
            <div class="n">{{ $current['badges']['super'] ?? 0 }} / {{ $current['badges']['great'] ?? 0 }}</div>
            <div class="l">Super / Great</div>
            <div class="sub">Last week: {{ $previous['badges']['super'] ?? 0 }} / {{ $previous['badges']['great'] ?? 0 }}</div>
        </div>
        <div class="tv-card">
            <div class="n" style="color:#dc2626;">{{ $current['badges']['overdue'] ?? 0 }}</div>
            <div class="l">Overdue</div>
            <div class="sub">Last week: {{ $previous['badges']['overdue'] ?? 0 }}</div>
        </div>
        <div class="tv-card">
            <div class="n">{{ $current['avg_ratio'] !== null ? $current['avg_ratio'].'x' : '—' }}</div>
            <div class="l">Avg time ratio</div>
            <div class="sub">Last week: {{ ($previous['avg_ratio'] ?? null) !== null ? $previous['avg_ratio'].'x' : '—' }}</div>
        </div>
    </div>

    <div class="tv-panel">
        <h3 style="margin:0 0 12px;font-size:15px;font-weight:800;">Last 6 weeks</h3>
        <div style="overflow-x:auto;">
            <table class="tv-table">
                <thead>
                    <tr>
                        <th>Week</th>
                        <th>Score</th>
                        <th>Done</th>
                        <th>Stars</th>
                        <th>Super</th>
                        <th>Great</th>
                        <th>Overdue</th>
                        <th>Avg ratio</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(($trend['weeks'] ?? []) as $week)
                    <tr class="{{ !empty($week['is_current']) ? 'current' : '' }}">
                        <td>{{ $week['label'] }}{{ !empty($week['is_current']) ? ' (selected)' : '' }}</td>
                        <td>{{ $week['score'] }}</td>
                        <td>{{ $week['badges']['done'] }}/{{ $week['badges']['total'] }}</td>
                        <td>{{ $week['badges']['stars'] }}</td>
                        <td>{{ $week['badges']['super'] }}</td>
                        <td>{{ $week['badges']['great'] }}</td>
                        <td>{{ $week['badges']['overdue'] }}</td>
                        <td>{{ $week['avg_ratio'] !== null ? $week['avg_ratio'].'x' : '—' }}</td>
                        <td><span class="tv-status {{ $week['status']['color'] }}">{{ $week['status']['label'] }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="tv-panel">
        <h3 style="margin:0 0 12px;font-size:15px;font-weight:800;">Performance trend</h3>
        <div class="tv-chart-wrap"><canvas id="tvEmpTrendChart"></canvas></div>
    </div>

    <div class="tv-panel">
        <h3 style="margin:0 0 12px;font-size:15px;font-weight:800;">Selected week tasks</h3>
        @if(!empty($trend['current_tasks']))
        <div class="tv-tasks">
            @foreach($trend['current_tasks'] as $t)
            <div class="tv-task {{ $t['status'] }}">
                <strong>{{ $t['title'] }}</strong>
                <div>{{ $t['category_name'] ?? '—' }} · Day {{ $t['day_of_week'] }}</div>
                <div>
                    Budget {{ $t['allocated_minutes'] }}m
                    {{ !empty($t['started_at']) ? '· Start '.$t['started_at'] : '' }}
                    {{ !empty($t['ended_at']) ? '· End '.$t['ended_at'] : '' }}
                    {{ !empty($t['actual_minutes']) ? '· Took '.$t['actual_minutes'].'m' : '' }}
                    {{ !empty($t['performance_tier']) ? '· '.ucfirst($t['performance_tier']) : '' }}
                    {{ !empty($t['earned_star']) ? '· Star' : '' }}
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div style="color:#9ca3af;font-size:13px;">No tasks for this employee in the selected week.</div>
        @endif
    </div>
    @else
    <div class="tv-panel" style="color:#9ca3af;text-align:center;padding:24px;">Select an employee to view performance history.</div>
    @endif
    @endif

    @if($tab === 'charts')
    <div class="tv-panel">
        <h3 style="margin:0 0 12px;font-size:15px;font-weight:800;">Weekly performance charts</h3>
        <div class="tv-chart-wrap"><canvas id="tvChart"></canvas></div>
    </div>
    <div class="tv-panel">
        <h3 style="margin:0 0 12px;font-size:15px;font-weight:800;">Badge earners</h3>
        <div class="tv-chart-wrap"><canvas id="tvBadgeChart"></canvas></div>
    </div>
    @endif
</div>
@endsection

@section('javascript')
@if($tab === 'charts' || $tab === 'employee')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endif
@if($tab === 'charts')
<script>
(function(){
    const data = @json($chart);
    const commonOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    };
    new Chart(document.getElementById('tvChart'), {
        type: 'bar',
        data: {
            labels: data.names,
            datasets: [
                { label: 'Completed', data: data.completed, backgroundColor: '#86efac' },
                { label: 'Overdue', data: data.overdue, backgroundColor: '#fca5a5' },
            ]
        },
        options: commonOpts
    });
    new Chart(document.getElementById('tvBadgeChart'), {
        type: 'bar',
        data: {
            labels: data.names,
            datasets: [
                { label: 'Stars', data: data.stars, backgroundColor: '#fcd34d' },
                { label: 'Super', data: data.supers, backgroundColor: '#4ade80' },
                { label: 'Great', data: data.great, backgroundColor: '#fde047' },
            ]
        },
        options: commonOpts
    });
})();
</script>
@endif
@if($tab === 'employee')
<script>
(function(){
    const trend = @json(($employeeTrend['chart'] ?? ['labels'=>[], 'scores'=>[], 'done'=>[], 'overdue'=>[]]));
    const el = document.getElementById('tvEmpTrendChart');
    if(!el) return;
    new Chart(el, {
        type: 'line',
        data: {
            labels: trend.labels || [],
            datasets: [
                { label: 'Score', data: trend.scores || [], borderColor: '#7c5cfc', backgroundColor: 'rgba(124,92,252,.15)', tension: 0.25, fill: true },
                { label: 'Done', data: trend.done || [], borderColor: '#16a34a', backgroundColor: 'transparent', tension: 0.25 },
                { label: 'Overdue', data: trend.overdue || [], borderColor: '#dc2626', backgroundColor: 'transparent', tension: 0.25 },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
})();
</script>
@endif
@endsection
