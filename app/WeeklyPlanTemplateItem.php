<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WeeklyPlanTemplateItem extends Model
{
    protected $fillable = [
        'template_id', 'category_id', 'day_of_week', 'title',
        'task_time', 'checklist_count', 'sort_order',
    ];

    public function template()
    {
        return $this->belongsTo(WeeklyPlanTemplate::class, 'template_id');
    }

    public function category()
    {
        return $this->belongsTo(TaskCategory::class, 'category_id');
    }
}
