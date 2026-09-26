@php
    $status = $task->status ?: ($task->is_completed ? 'completed' : 'pending');
    $tier = $task->performance_tier;
    $alloc = (int) ($task->allocated_minutes ?: 60);
    $allocH = intdiv($alloc, 60);
    $allocM = $alloc % 60;
    $allocLabel = $allocH > 0 && $allocM > 0 ? "{$allocH}h {$allocM}m" : ($allocH > 0 ? "{$allocH}h" : "{$allocM}m");
    $classes = 'et-task status-'.$status;
    if ($task->is_completed) $classes .= ' done';
    if ($tier) $classes .= ' tier-'.$tier;
    $startedLabel = $task->started_at ? \Carbon\Carbon::parse($task->started_at)->format('H:i') : null;
    $endedLabel = $task->ended_at ? \Carbon\Carbon::parse($task->ended_at)->format('d M H:i') : null;
@endphp
<div class="{{ $classes }}" id="task-{{ $task->id }}{{ !empty($sectionPrefix) ? '-'.$sectionPrefix : '' }}" data-id="{{ $task->id }}">
    <div class="et-task-row">
        @if($canManage ?? false)
        <input type="checkbox" {{ $task->is_completed ? 'checked' : '' }} disabled title="Employee uses Start / End">
        @endif
        <div style="flex:1;">
            <div class="et-task-title">{{ $task->title }}</div>
            <div class="et-task-meta">
                <span class="et-alloc"><i class="far fa-hourglass"></i> {{ $allocLabel }}</span>
                @if($task->task_time)<span><i class="far fa-clock"></i> {{ substr($task->task_time, 0, 5) }}</span>@endif
                @if($task->checklist_count > 1)<span><i class="far fa-check-square"></i> {{ $task->checklist_count }}</span>@endif
                @if($startedLabel && ! $endedLabel)
                <span><i class="fas fa-play"></i> Started {{ $startedLabel }}</span>
                @endif
                @if($task->is_completed)
                    @if($task->earned_star)<span class="et-badge-pill star" style="padding:2px 6px;"><i class="fas fa-star"></i></span>@endif
                    @if($tier === 'super')<span class="et-badge-pill super" style="padding:2px 6px;">Super</span>
                    @elseif($tier === 'great')<span class="et-badge-pill great" style="padding:2px 6px;">Great</span>@endif
                    <span class="et-done-at"><i class="fas fa-check"></i> {{ $endedLabel ?: 'Done' }}</span>
                @elseif($status === 'overdue')
                <span style="color:#dc2626;font-weight:700;">Overdue</span>
                @endif
            </div>
            @if($personalOnly ?? false)
            <div class="et-task-actions">
                @if(! $task->is_completed && ! $task->started_at)
                <button type="button" class="et-btn-sm et-btn-start" onclick="startTask({{ $task->id }})"><i class="fas fa-play"></i> Start</button>
                @elseif(! $task->is_completed && $task->started_at)
                <button type="button" class="et-btn-sm et-btn-end" onclick="endTask({{ $task->id }})"><i class="fas fa-stop"></i> End</button>
                @endif
            </div>
            @endif
            @if($canManage ?? false)
            <button type="button" class="et-task-del" onclick="deleteTask({{ $task->id }})">Remove</button>
            @endif
        </div>
    </div>
</div>
