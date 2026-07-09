<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TaskCategory extends Model
{
    protected $fillable = ['business_id', 'name', 'color', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function templateItems()
    {
        return $this->hasMany(WeeklyPlanTemplateItem::class, 'category_id');
    }

    public function planItems()
    {
        return $this->hasMany(EmployeeWeeklyPlanItem::class, 'category_id');
    }

    public function isInUse(): bool
    {
        return $this->templateItems()->exists() || $this->planItems()->exists();
    }
}
