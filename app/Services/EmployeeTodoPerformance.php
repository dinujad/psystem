<?php

namespace App\Services;

use App\EmployeeWeeklyPlan;
use App\EmployeeWeeklyPlanItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class EmployeeTodoPerformance
{
    private static ?bool $hasColumns = null;

    public function hasPerformanceColumns(): bool
    {
        if (self::$hasColumns === null) {
            self::$hasColumns = Schema::hasColumn('employee_weekly_plan_items', 'status')
                && Schema::hasColumn('employee_weekly_plan_items', 'allocated_minutes');
        }

        return self::$hasColumns;
    }

    public function assignedDate(EmployeeWeeklyPlanItem $item, ?EmployeeWeeklyPlan $plan = null): Carbon
    {
        if (! $plan) {
            if ($item->relationLoaded('plan')) {
                $plan = $item->plan;
            } else {
                $item->loadMissing('plan');
                $plan = $item->plan;
            }
        }

        if (! $plan || empty($plan->week_start_date)) {
            return Carbon::today()->startOfDay();
        }

        $weekStart = Carbon::parse($plan->week_start_date)->startOfDay();

        return $weekStart->copy()->addDays(max(0, (int) $item->day_of_week - 1))->startOfDay();
    }

    public function refreshOverdueForPlan(EmployeeWeeklyPlan $plan): void
    {
        if (! $this->hasPerformanceColumns()) {
            return;
        }

        $today = Carbon::today();
        $plan->loadMissing('items');

        foreach ($plan->items as $item) {
            $item->setRelation('plan', $plan);

            if ($item->is_completed || ($item->status ?? null) === 'completed') {
                continue;
            }

            $assigned = $this->assignedDate($item, $plan);
            if ($assigned->lt($today) && ($item->status ?? null) !== 'overdue') {
                $item->update(['status' => 'overdue']);
            }
        }
    }

    public function refreshOverdueForBusiness(int $businessId): void
    {
        $weekStart = EmployeeWeeklyPlan::normalizeWeekStart();
        $plans = EmployeeWeeklyPlan::where('business_id', $businessId)
            ->whereBetween('week_start_date', [
                $weekStart->copy()->subWeeks(2)->toDateString(),
                $weekStart->toDateString(),
            ])
            ->with('items')
            ->get();

        foreach ($plans as $plan) {
            $this->refreshOverdueForPlan($plan);
        }
    }

    public function start(EmployeeWeeklyPlanItem $item): array
    {
        $item->loadMissing('plan', 'category');

        if ($item->is_completed || $item->status === 'completed') {
            return ['success' => false, 'message' => 'Task already completed.'];
        }
        if ($item->started_at) {
            return ['success' => false, 'message' => 'Task already started.', 'item' => $item];
        }

        $assigned = $this->assignedDate($item);
        $earlyStart = Carbon::today()->lt($assigned);

        $item->update([
            'started_at'  => now(),
            'status'      => 'in_progress',
            'early_start' => $earlyStart,
        ]);

        return [
            'success'     => true,
            'early_start' => $earlyStart,
            'item'        => $item->fresh(['category', 'plan']),
            'popup'       => $earlyStart ? [
                'type'    => 'early_start',
                'title'   => 'Congratulations!',
                'message' => 'You started this task early — great initiative!',
            ] : null,
        ];
    }

    public function end(EmployeeWeeklyPlanItem $item): array
    {
        $item->loadMissing('plan', 'category');

        if ($item->is_completed || $item->status === 'completed') {
            return ['success' => false, 'message' => 'Task already completed.'];
        }
        if (! $item->started_at) {
            return ['success' => false, 'message' => 'Start the task before ending it.'];
        }

        $endedAt = now();
        $result = $this->computeTiers($item, $endedAt);

        $item->update([
            'ended_at'         => $endedAt,
            'completed_at'     => $endedAt,
            'is_completed'     => true,
            'completed_count'  => $item->checklist_count,
            'status'           => 'completed',
            'performance_tier' => $result['tier'],
            'earned_star'      => $result['earned_star'],
        ]);

        $item = $item->fresh(['category', 'plan']);

        return [
            'success' => true,
            'item'    => $item,
            'popup'   => $result['popup'],
            'result'  => $result,
        ];
    }

    public function computeTiers(EmployeeWeeklyPlanItem $item, ?Carbon $endedAt = null): array
    {
        $endedAt = $endedAt ?? ($item->ended_at ? Carbon::parse($item->ended_at) : now());
        $startedAt = $item->started_at ? Carbon::parse($item->started_at) : $endedAt;
        $allocated = max(1, (int) ($item->allocated_minutes ?: 60));
        $actualMinutes = max(1, (int) ceil($startedAt->diffInSeconds($endedAt) / 60));
        $assigned = $this->assignedDate($item);
        $finishedBeforeDay = $endedAt->copy()->startOfDay()->lt($assigned);
        $ratio = $actualMinutes / $allocated;

        $tier = 'normal';
        $earnedStar = false;

        if ($finishedBeforeDay && $ratio <= 1.0) {
            $tier = 'super';
            $earnedStar = true;
        } elseif ($ratio < 0.5) {
            $tier = 'super';
        } elseif ($ratio <= 1.0) {
            $tier = 'great';
        }

        $popup = null;
        if ($earnedStar) {
            $popup = [
                'type'    => 'star_super',
                'title'   => 'Super Performer + Star!',
                'message' => 'You finished before the assigned day and within the time budget. Outstanding work!',
            ];
        } elseif ($tier === 'super') {
            $popup = [
                'type'    => 'super',
                'title'   => 'Super Performer!',
                'message' => 'You completed this task in less than half the allocated time.',
            ];
        } elseif ($tier === 'great') {
            $popup = [
                'type'    => 'great',
                'title'   => 'Great job!',
                'message' => 'You finished within the allocated time. Keep it up!',
            ];
        }

        return [
            'tier'            => $tier,
            'earned_star'     => $earnedStar,
            'actual_minutes'  => $actualMinutes,
            'allocated_minutes' => $allocated,
            'finished_before_day' => $finishedBeforeDay,
            'popup'           => $popup,
        ];
    }

    /**
     * @param  Collection<int, EmployeeWeeklyPlanItem>  $items
     */
    public function weekBadgeStats(Collection $items): array
    {
        $completed = $items->filter(fn ($i) => $i->is_completed || $i->status === 'completed');

        return [
            'stars'  => $completed->where('earned_star', true)->count(),
            'super'  => $completed->where('performance_tier', 'super')->count(),
            'great'  => $completed->where('performance_tier', 'great')->count(),
            'done'   => $completed->count(),
            'total'  => $items->count(),
            'overdue'=> $items->where('status', 'overdue')->count(),
        ];
    }

    /**
     * @param  Collection<int, EmployeeWeeklyPlanItem>  $items
     * @return array{color:string,label:string,key:string}
     */
    public function myStatus(Collection $items): array
    {
        if ($items->where('status', 'overdue')->isNotEmpty()) {
            return ['color' => 'red', 'label' => 'Overdue', 'key' => 'overdue'];
        }

        $completed = $items->filter(fn ($i) => $i->is_completed || $i->status === 'completed');
        if ($completed->where('performance_tier', 'super')->isNotEmpty()
            || $completed->where('earned_star', true)->isNotEmpty()) {
            return ['color' => 'green', 'label' => 'Super Performer', 'key' => 'super'];
        }
        if ($completed->where('performance_tier', 'great')->isNotEmpty()) {
            return ['color' => 'yellow', 'label' => 'Great', 'key' => 'great'];
        }

        return ['color' => 'green', 'label' => 'On Track', 'key' => 'on_track'];
    }

    public function itemToArray(EmployeeWeeklyPlanItem $item): array
    {
        $actual = null;
        if ($item->started_at && $item->ended_at) {
            $actual = (int) ceil(Carbon::parse($item->started_at)->diffInSeconds(Carbon::parse($item->ended_at)) / 60);
        }

        $fmt = function ($value) {
            if (! $value) {
                return null;
            }

            return Carbon::parse($value)->format('d M H:i');
        };

        return [
            'id'                 => $item->id,
            'title'              => $item->title,
            'task_time'          => $item->task_time,
            'checklist_count'    => $item->checklist_count,
            'allocated_minutes'  => (int) ($item->allocated_minutes ?: 60),
            'is_completed'       => (bool) $item->is_completed,
            'completed_at'       => $fmt($item->completed_at),
            'started_at'         => $fmt($item->started_at),
            'ended_at'           => $fmt($item->ended_at),
            'started_at_raw'     => $item->started_at ? Carbon::parse($item->started_at)->toIso8601String() : null,
            'status'             => $item->status ?: ($item->is_completed ? 'completed' : 'pending'),
            'performance_tier'   => $item->performance_tier,
            'earned_star'        => (bool) $item->earned_star,
            'early_start'        => (bool) $item->early_start,
            'actual_minutes'     => $actual,
            'source'             => $item->source,
            'category_id'        => $item->category_id,
            'day_of_week'        => $item->day_of_week,
            'category_name'      => $item->category?->name,
            'category_color'     => $item->category?->color,
            'employee_id'        => $item->relationLoaded('plan') ? $item->plan?->employee_id : null,
        ];
    }
}
