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
</style>
@endsection

@section('content')
<div class="tv-page">
    <div class="tv-head">
        <div class="tv-title"><i class="fas fa-chart-line" style="color:#7c5cfc;"></i> Task View</div>
        <div class="tv-week">
            <a class="tv-btn outline" href="{{ route('employee-todos.task-view', ['week' => $prevWeek, 'tab' => $tab]) }}"><i class="fas fa-chevron-left"></i> Prev</a>
            <span class="tv-pill">{{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M Y') }}</span>
            <a class="tv-btn outline" href="{{ route('employee-todos.task-view', ['week' => $nextWeek, 'tab' => $tab]) }}">Next <i class="fas fa-chevron-right"></i></a>
            <a class="tv-btn outline" href="{{ route('employee-todos.task-view', ['week' => now()->startOfWeek()->toDateString(), 'tab' => $tab]) }}">This Week</a>
        </div>
    </div>

    <div class="tv-tabs">
        <a class="tv-tab {{ $tab === 'overview' ? 'active' : '' }}" href="{{ route('employee-todos.task-view', ['week' => $weekStart->toDateString(), 'tab' => 'overview']) }}">Overview</a>
        <a class="tv-tab {{ $tab === 'performance' ? 'active' : '' }}" href="{{ route('employee-todos.task-view', ['week' => $weekStart->toDateString(), 'tab' => 'performance']) }}">Performance</a>
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
            @if(count($row['tasks']))
            <div class="tv-tasks">
                @foreach($row['tasks'] as $t)
                <div class="tv-task {{ $t['status'] }}">
                    <strong>{{ $t['title'] }}</strong>
                    <div>{{ $t['category_name'] ?? '—' }} · Day {{ $t['day_of_week'] }}</div>
                    <div>Budget {{ $t['allocated_minutes'] }}m
                        @if($t['started_at']) · Start {{ $t['started_at'] }}@endif
                        @if($t['ended_at']) · End {{ $t['ended_at'] }}@endif
                        @if($t['actual_minutes']) · Took {{ $t['actual_minutes'] }}m@endif
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
        @foreach($rows as $row)
        <div class="tv-rank">
            <div class="tv-rank-num {{ $row['rank'] <= 3 ? 'top' : '' }}">{{ $row['rank'] }}</div>
            <div style="flex:1;">
                <div style="font-weight:800;">{{ $row['employee']['name'] }}</div>
                <div style="font-size:12px;color:#6b7280;">
                    Score {{ $row['score'] }} · ⭐ {{ $row['badges']['stars'] }} · Super {{ $row['badges']['super'] }} · Great {{ $row['badges']['great'] }} · Done {{ $row['badges']['done'] }}/{{ $row['badges']['total'] }}
                    @if($row['avg_ratio'] !== null) · Avg time ratio {{ $row['avg_ratio'] }}x @endif
                </div>
            </div>
            <span class="tv-status {{ $row['status']['color'] }}">{{ $row['status']['label'] }}</span>
        </div>
        @endforeach
    </div>
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
@if($tab === 'charts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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
@endsection
