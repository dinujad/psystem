<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeWeeklyPlanItem extends Model
{
    protected $fillable = [
        'employee_weekly_plan_id', 'category_id', 'day_of_week', 'title', 'task_time',
        'checklist_count', 'completed_count', 'is_completed', 'completed_at', 'source', 'sort_order',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
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
        ]);
    }
}
