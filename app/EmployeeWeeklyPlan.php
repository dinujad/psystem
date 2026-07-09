<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class EmployeeWeeklyPlan extends Model
{
    protected $fillable = [
        'business_id', 'employee_id', 'week_start_date', 'template_id', 'notes', 'created_by',
    ];

    protected $casts = [
        'week_start_date' => 'date',
    ];

    public static function dayLabels(): array
    {
        return [
            1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
            5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
        ];
    }

    public static function dayShortLabels(): array
    {
        return [
            1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu',
            5 => 'Fri', 6 => 'Sat', 7 => 'Sun',
        ];
    }

    public static function normalizeWeekStart(?string $date = null): Carbon
    {
        $d = $date ? Carbon::parse($date) : Carbon::now();

        return $d->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    public function items()
    {
        return $this->hasMany(EmployeeWeeklyPlanItem::class, 'employee_weekly_plan_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function template()
    {
        return $this->belongsTo(WeeklyPlanTemplate::class, 'template_id');
    }

    public function weekEnd(): Carbon
    {
        return $this->week_start_date->copy()->addDays(6);
    }

    public function completionStats(): array
    {
        $items = $this->items;
        $total = $items->count();
        $done  = $items->where('is_completed', true)->count();

        return [
            'total'      => $total,
            'completed'  => $done,
            'percent'    => $total > 0 ? round(($done / $total) * 100) : 0,
        ];
    }

    public static function userDisplayName(?User $user): string
    {
        if (! $user) {
            return 'Unknown';
        }

        $name = trim(($user->surname ?? '').' '.($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $name ?: ($user->username ?? 'User');
    }
}
