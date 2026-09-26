<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeWeeklyPlanItem extends Model
{
    protected $fillable = [
        'employee_weekly_plan_id', 'category_id', 'day_of_week', 'title', 'task_time',
        'checklist_count', 'allocated_minutes', 'completed_count', 'is_completed', 'completed_at',
        'started_at', 'ended_at', 'status', 'performance_tier', 'earned_star', 'early_start',
        'source', 'sort_order',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'earned_star'  => 'boolean',
        'early_start'  => 'boolean',
        'completed_at' => 'datetime',
        'started_at'   => 'datetime',
        'ended_at'     => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(EmployeeWeeklyPlan::class, 'employee_weekly_plan_id');
    }

    public function category()
    {
        return $this->belongsTo(TaskCategory::class, 'category_id');
    }

    public function markCompleted(bool $completed): void
    {
        $this->update([
            'is_completed'    => $completed,
            'completed_count' => $completed ? $this->checklist_count : 0,
            'completed_at'    => $completed ? now() : null,
            'ended_at'        => $completed ? ($this->ended_at ?? now()) : null,
            'status'          => $completed ? 'completed' : ($this->started_at ? 'in_progress' : 'pending'),
            'performance_tier'=> $completed ? $this->performance_tier : null,
            'earned_star'     => $completed ? $this->earned_star : false,
        ]);
    }

    public function allocatedLabel(): string
    {
        $mins = max(0, (int) ($this->allocated_minutes ?: 0));
        $h = intdiv($mins, 60);
        $m = $mins % 60;
        if ($h > 0 && $m > 0) {
            return $h.'h '.$m.'m';
        }
        if ($h > 0) {
            return $h.'h';
        }

        return $m.'m';
    }
}
