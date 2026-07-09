<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WeeklyPlanTemplate extends Model
{
    protected $fillable = ['business_id', 'name', 'description', 'created_by'];

    public function items()
    {
        return $this->hasMany(WeeklyPlanTemplateItem::class, 'template_id')
            ->orderBy('category_id')
            ->orderBy('day_of_week')
            ->orderBy('sort_order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function employeePlans()
    {
        return $this->hasMany(EmployeeWeeklyPlan::class, 'template_id');
    }
}
