<?php

namespace App\Http\Controllers;

use App\EmployeeWeeklyPlan;
use App\EmployeeWeeklyPlanItem;
use App\Services\EmployeeTodoNotifier;
use App\Services\EmployeeTodoPerformance;
use App\TaskCategory;
use App\User;
use App\WeeklyPlanTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeTodoController extends Controller
{
    use Concerns\EmployeeTodoAccess;

    private function employees()
    {
        return User::where('business_id', $this->businessId())
            ->user()
            ->where('is_cmmsn_agnt', 0)
            ->orderBy('first_name')
            ->get()
            ->map(fn ($u) => [
                'id'       => $u->id,
                'name'     => EmployeeWeeklyPlan::userDisplayName($u),
                'initials' => strtoupper(substr($u->first_name ?? $u->username ?? 'U', 0, 1).substr($u->last_name ?? '', 0, 1)),
            ]);
    }

    private function getOrCreateEmployeePlan(int $employeeId, Carbon $weekStart): EmployeeWeeklyPlan
    {
        $this->assertEmployeeInBusiness($employeeId);

        return EmployeeWeeklyPlan::firstOrCreate(
            [
                'business_id'     => $this->businessId(),
                'employee_id'     => $employeeId,
                'week_start_date' => $weekStart->toDateString(),
            ],
            ['created_by' => auth()->id()]
        );
    }

    public function index(Request $request)
    {
        try {
            if (! $this->canManage()) {
                return redirect()->route('employee-todos.my-week', $request->only('week'));
            }

            return $this->renderTodoView($this->weekView($request, false), $request);
        } catch (\Throwable $e) {
            return $this->todoDebugResponse($e, $request);
        }
    }

    public function myWeek(Request $request)
    {
        try {
            if (! $this->isStaffUser()) {
                abort(403, 'You do not have access to To-Do.');
            }

            $request->merge(['employee' => auth()->id()]);

            return $this->renderTodoView($this->weekView($request, true), $request);
        } catch (\Throwable $e) {
            return $this->todoDebugResponse($e, $request);
        }
    }

    public function taskView(Request $request)
    {
        try {
            return $this->renderTodoView($this->buildTaskView($request), $request);
        } catch (\Throwable $e) {
            return $this->todoDebugResponse($e, $request);
        }
    }

    private function renderTodoView($view, Request $request)
    {
        if ($request->query('todo_debug')) {
            return response($view->render());
        }

        return $view;
    }

    private function todoDebugResponse(\Throwable $e, Request $request)
    {
        report($e);

        if ($request->query('todo_debug')) {
            $html = '<pre style="white-space:pre-wrap;padding:24px;background:#111;color:#86efac;font:13px/1.45 monospace;">'
                .e($e->getMessage())."\n\n"
                .e($e->getFile().':'.$e->getLine())."\n\n"
                .e($e->getTraceAsString())
                .'</pre>';

            return response($html, 500);
        }

        throw $e;
    }

    private function weekView(Request $request, bool $personalOnly)
    {
        $weekStart    = EmployeeWeeklyPlan::normalizeWeekStart($request->get('week'));
        $canManage    = $this->canManage() && ! $personalOnly;
        $allEmployees = $this->employees();
        $employeeId   = $request->get('employee');

        if ($personalOnly || ! $canManage) {
            $employeeId = auth()->id();
        } elseif (! $employeeId || $employeeId === 'all') {
            $employeeId = $allEmployees->first()['id'] ?? null;
        }

        $allCategories = TaskCategory::forBusiness($this->businessId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $plan  = null;
        $items = collect();
        $dayStats = [];
        $weekStats = ['total' => 0, 'completed' => 0, 'percent' => 0];
        $visibleCategoryIds = [];

        $badgeStats = ['stars' => 0, 'super' => 0, 'great' => 0, 'done' => 0, 'total' => 0, 'overdue' => 0];
        $myStatus = ['color' => 'green', 'label' => 'On Track', 'key' => 'on_track'];
        $todayItems = collect();
        $overdueItems = collect();
        $todayDow = (int) Carbon::now()->dayOfWeekIso;
        $perf = null;

        if ($employeeId) {
            $plan = $this->getOrCreateEmployeePlan((int) $employeeId, $weekStart);
            try {
                $perf = app(EmployeeTodoPerformance::class);
                $perf->refreshOverdueForPlan($plan);
            } catch (\Throwable $e) {
                \Log::warning('employee-todos overdue refresh failed: '.$e->getMessage());
            }
            $flatItems = $plan->items()
                ->with('category')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
            $plan->setRelation('items', $flatItems);
            $items = $flatItems->groupBy(fn ($i) => $i->category_id.'_'.$i->day_of_week);

            // Only show category rows that already have tasks for this employee/week
            $visibleCategoryIds = $flatItems->pluck('category_id')->unique()->filter()->values()->all();

            $weekStats = $plan->completionStats();
            try {
                $perf = $perf ?: app(EmployeeTodoPerformance::class);
                $badgeStats = $perf->weekBadgeStats($flatItems);
                $myStatus = $perf->myStatus($flatItems);
            } catch (\Throwable $e) {
                \Log::warning('employee-todos badge stats failed: '.$e->getMessage());
            }
            $todayItems = $flatItems->where('day_of_week', $todayDow)->values();
            $overdueItems = $flatItems->filter(fn ($i) => ($i->status ?? null) === 'overdue')->values();

            foreach (EmployeeWeeklyPlan::dayLabels() as $d => $label) {
                $dayItems = $flatItems->where('day_of_week', $d);
                $total    = $dayItems->count();
                $done     = $dayItems->where('is_completed', true)->count();
                $dayStats[$d] = [
                    'total'   => $total,
                    'done'    => $done,
                    'percent' => $total > 0 ? round(($done / $total) * 100) : 0,
                ];
            }
        }

        $categories = $allCategories->whereIn('id', $visibleCategoryIds)->values();

        $templates = $canManage
            ? WeeklyPlanTemplate::where('business_id', $this->businessId())->orderBy('name')->get(['id', 'name'])
            : collect();

        $prevWeek = $weekStart->copy()->subWeek()->toDateString();
        $nextWeek = $weekStart->copy()->addWeek()->toDateString();
        $weekEnd  = $weekStart->copy()->addDays(6);

        $days = [];
        foreach (EmployeeWeeklyPlan::dayLabels() as $num => $label) {
            $days[$num] = [
                'label' => $label,
                'short' => EmployeeWeeklyPlan::dayShortLabels()[$num],
                'date'  => $weekStart->copy()->addDays($num - 1),
            ];
        }

        $selectedEmp = $employeeId
            ? $allEmployees->firstWhere('id', (int) $employeeId)
            : null;

        return view('employee-todos.index', compact(
            'plan', 'weekStart', 'weekEnd', 'prevWeek', 'nextWeek',
            'allEmployees', 'categories', 'allCategories', 'items', 'days', 'dayStats', 'weekStats',
            'canManage', 'employeeId', 'personalOnly', 'templates', 'selectedEmp',
            'badgeStats', 'myStatus', 'todayItems', 'overdueItems', 'todayDow'
        ));
    }

    private function isStaffUser(): bool
    {
        $user = auth()->user();

        return $user
            && $user->user_type === 'user'
            && ! $user->is_cmmsn_agnt
            && (int) $user->business_id === $this->businessId();
    }

    public function storeItem(Request $request)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'week'              => ['required', 'date'],
            'employee_id'       => ['required', 'integer', 'exists:users,id'],
            'category_id'       => ['required', 'integer'],
            'day_of_week'       => ['required', 'integer', 'min:1', 'max:7'],
            'title'             => ['required', 'string', 'max:200'],
            'task_time'         => ['nullable', 'string', 'max:10'],
            'checklist_count'   => ['nullable', 'integer', 'min:1', 'max:99'],
            'allocated_hours'   => ['nullable', 'integer', 'min:0', 'max:99'],
            'allocated_minutes' => ['nullable', 'integer', 'min:0', 'max:59'],
        ]);

        $allocated = ((int) ($data['allocated_hours'] ?? 0) * 60) + (int) ($data['allocated_minutes'] ?? 0);
        if ($allocated < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Allocated time is required (hours and/or minutes).',
            ], 422);
        }

        $weekStart = EmployeeWeeklyPlan::normalizeWeekStart($data['week']);
        $plan      = $this->getOrCreateEmployeePlan((int) $data['employee_id'], $weekStart);
        $this->assertCategory($data['category_id']);

        $maxSort = $plan->items()
            ->where('category_id', $data['category_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->max('sort_order');

        $item = EmployeeWeeklyPlanItem::create([
            'employee_weekly_plan_id' => $plan->id,
            'category_id'             => $data['category_id'],
            'day_of_week'             => $data['day_of_week'],
            'title'                   => trim($data['title']),
            'task_time'               => $data['task_time'] ?? null,
            'checklist_count'         => max(1, (int) ($data['checklist_count'] ?? 1)),
            'allocated_minutes'       => $allocated,
            'completed_count'         => 0,
            'is_completed'            => false,
            'status'                  => 'pending',
            'source'                  => 'manual',
            'sort_order'              => ($maxSort ?? 0) + 1,
        ]);

        $whatsapp = null;
        if ($this->canManage() && (int) $data['employee_id'] !== auth()->id()) {
            $employee = User::find((int) $data['employee_id']);
            if ($employee) {
                $whatsapp = app(EmployeeTodoNotifier::class)->notifyNewTask(
                    $employee,
                    $item->load('category'),
                    $weekStart
                );
            }
        }

        return response()->json([
            'success'  => true,
            'item'     => $this->itemPayload($item->load('category'), $plan),
            'stats'    => $this->statsPayload($plan),
            'whatsapp' => $whatsapp,
        ]);
    }

    public function toggleItem(EmployeeWeeklyPlanItem $item)
    {
        $this->authorizeItem($item);
        $item->load('plan');

        if ((int) $item->plan->employee_id !== auth()->id()) {
            abort(403, 'Only the assigned employee can mark tasks complete.');
        }

        // Timed workflow: End completes; toggle only allows un-complete for corrections
        if (! $item->is_completed) {
            return response()->json([
                'success' => false,
                'message' => 'Use Start / End to complete timed tasks.',
            ], 422);
        }

        $item->markCompleted(false);
        $item->update([
            'started_at'       => null,
            'ended_at'         => null,
            'status'           => 'pending',
            'performance_tier' => null,
            'earned_star'      => false,
            'early_start'      => false,
        ]);

        return response()->json([
            'success' => true,
            'item'    => $this->itemPayload($item->fresh(['category', 'plan']), $item->plan),
            'stats'   => $this->statsPayload($item->plan->fresh()),
        ]);
    }

    public function startItem(EmployeeWeeklyPlanItem $item)
    {
        $this->authorizeItem($item);
        $item->load('plan');

        if ((int) $item->plan->employee_id !== auth()->id()) {
            abort(403, 'Only the assigned employee can start this task.');
        }

        $result = app(EmployeeTodoPerformance::class)->start($item);

        return response()->json([
            'success'     => $result['success'],
            'message'     => $result['message'] ?? null,
            'early_start' => $result['early_start'] ?? false,
            'popup'       => $result['popup'] ?? null,
            'item'        => isset($result['item'])
                ? $this->itemPayload($result['item'], $item->plan)
                : null,
            'stats'       => $this->statsPayload($item->plan->fresh()),
        ], $result['success'] ? 200 : 422);
    }

    public function endItem(EmployeeWeeklyPlanItem $item)
    {
        $this->authorizeItem($item);
        $item->load('plan');

        if ((int) $item->plan->employee_id !== auth()->id()) {
            abort(403, 'Only the assigned employee can end this task.');
        }

        $result = app(EmployeeTodoPerformance::class)->end($item);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'] ?? null,
            'popup'   => $result['popup'] ?? null,
            'result'  => $result['result'] ?? null,
            'item'    => isset($result['item'])
                ? $this->itemPayload($result['item'], $item->plan)
                : null,
            'stats'   => $this->statsPayload($item->plan->fresh()),
        ], $result['success'] ? 200 : 422);
    }

    private function buildTaskView(Request $request)
    {
        $this->authorizeManage();

        $weekStart = EmployeeWeeklyPlan::normalizeWeekStart($request->get('week'));
        $weekEnd   = $weekStart->copy()->addDays(6);
        $prevWeek  = $weekStart->copy()->subWeek()->toDateString();
        $nextWeek  = $weekStart->copy()->addWeek()->toDateString();
        $tab       = in_array($request->get('tab'), ['overview', 'performance', 'charts'], true)
            ? $request->get('tab')
            : 'overview';

        $perf = app(EmployeeTodoPerformance::class);
        try {
            $perf->refreshOverdueForBusiness($this->businessId());
        } catch (\Throwable $e) {
            \Log::warning('employee-todos task-view overdue refresh failed: '.$e->getMessage());
        }

        $employees = $this->employees();
        $plans = EmployeeWeeklyPlan::where('business_id', $this->businessId())
            ->where('week_start_date', $weekStart->toDateString())
            ->with(['items.category', 'employee'])
            ->get()
            ->keyBy('employee_id');

        $todayDow = (int) Carbon::now()->dayOfWeekIso;
        $rows = [];
        $chart = [
            'names'     => [],
            'completed' => [],
            'overdue'   => [],
            'super'     => [],
            'great'     => [],
            'stars'     => [],
        ];
        $overview = [
            'working'   => 0,
            'overdue'   => 0,
            'completed' => 0,
            'pending'   => 0,
        ];

        foreach ($employees as $emp) {
            $plan = $plans->get($emp['id']);
            $items = $plan ? $plan->items : collect();
            $badges = $perf->weekBadgeStats($items);
            $status = $perf->myStatus($items);
            $todayDone = $items->where('day_of_week', $todayDow)->where('is_completed', true)->count();
            $todayTotal = $items->where('day_of_week', $todayDow)->count();
            $inProgress = $items->where('status', 'in_progress')->count();

            if ($inProgress > 0) {
                $overview['working']++;
            }
            if ($badges['overdue'] > 0) {
                $overview['overdue']++;
            }
            $overview['completed'] += $badges['done'];
            $overview['pending'] += max(0, $badges['total'] - $badges['done']);

            $taskCards = $items->map(fn ($i) => $perf->itemToArray($i))->values()->all();

            $avgRatio = null;
            $completedTimed = $items->filter(fn ($i) => $i->started_at && $i->ended_at && $i->allocated_minutes);
            if ($completedTimed->isNotEmpty()) {
                $ratios = $completedTimed->map(function ($i) {
                    $actual = max(1, (int) ceil(Carbon::parse($i->started_at)->diffInSeconds(Carbon::parse($i->ended_at)) / 60));

                    return $actual / max(1, (int) $i->allocated_minutes);
                });
                $avgRatio = round($ratios->avg(), 2);
            }

            $rows[] = [
                'employee'   => $emp,
                'badges'     => $badges,
                'status'     => $status,
                'today_done' => $todayDone,
                'today_total'=> $todayTotal,
                'in_progress'=> $inProgress,
                'avg_ratio'  => $avgRatio,
                'tasks'      => $taskCards,
                'score'      => ($badges['stars'] * 3) + ($badges['super'] * 2) + $badges['great'],
            ];

            $chart['names'][] = $emp['name'];
            $chart['completed'][] = $badges['done'];
            $chart['overdue'][] = $badges['overdue'];
            $chart['super'][] = $badges['super'];
            $chart['great'][] = $badges['great'];
            $chart['stars'][] = $badges['stars'];
        }

        usort($rows, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return ($a['badges']['done'] <=> $b['badges']['done']) * -1;
            }

            return $b['score'] <=> $a['score'];
        });

        $rank = 1;
        foreach ($rows as &$row) {
            $row['rank'] = $rank++;
        }
        unset($row);

        $days = [];
        foreach (EmployeeWeeklyPlan::dayLabels() as $num => $label) {
            $days[$num] = [
                'label' => $label,
                'short' => EmployeeWeeklyPlan::dayShortLabels()[$num],
                'date'  => $weekStart->copy()->addDays($num - 1),
            ];
        }

        return view('employee-todos.task-view', compact(
            'weekStart', 'weekEnd', 'prevWeek', 'nextWeek', 'tab',
            'rows', 'overview', 'chart', 'days', 'todayDow'
        ));
    }

    public function deleteItem(EmployeeWeeklyPlanItem $item)
    {
        $this->authorizeItem($item, true);
        $plan = $item->plan;
        $item->delete();

        return response()->json([
            'success' => true,
            'stats'   => $this->statsPayload($plan->fresh()),
        ]);
    }

    public function assignTemplate(Request $request)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'week'        => ['required', 'date'],
            'employee_id' => ['required', 'integer', 'exists:users,id'],
            'template_id' => ['required', 'integer', 'exists:weekly_plan_templates,id'],
            'mode'        => ['required', 'in:merge,replace'],
        ]);

        $weekStart = EmployeeWeeklyPlan::normalizeWeekStart($data['week']);
        $template  = WeeklyPlanTemplate::where('business_id', $this->businessId())
            ->where('id', $data['template_id'])
            ->with('items')
            ->firstOrFail();

        $plan = $this->getOrCreateEmployeePlan((int) $data['employee_id'], $weekStart);
        $hasExisting = $plan->items()->exists();

        if ($hasExisting && $data['mode'] === 'replace') {
            $plan->items()->delete();
        }

        $addedCount = 0;

        DB::transaction(function () use ($plan, $template, $data, &$addedCount) {
            foreach ($template->items as $src) {
                if ($data['mode'] === 'merge') {
                    $exists = $plan->items()
                        ->where('category_id', $src->category_id)
                        ->where('day_of_week', $src->day_of_week)
                        ->where('title', $src->title)
                        ->where('source', 'template')
                        ->exists();
                    if ($exists) {
                        continue;
                    }
                }

                EmployeeWeeklyPlanItem::create([
                    'employee_weekly_plan_id' => $plan->id,
                    'category_id'             => $src->category_id,
                    'day_of_week'             => $src->day_of_week,
                    'title'                   => $src->title,
                    'task_time'               => $src->task_time,
                    'checklist_count'         => $src->checklist_count,
                    'allocated_minutes'       => max(1, (int) ($src->allocated_minutes ?: 60)),
                    'completed_count'         => 0,
                    'is_completed'            => false,
                    'status'                  => 'pending',
                    'source'                  => 'template',
                    'sort_order'              => $src->sort_order,
                ]);
                $addedCount++;
            }

            $plan->update(['template_id' => $template->id]);
        });

        $whatsapp = null;
        if ($addedCount > 0) {
            $employee = User::find((int) $data['employee_id']);
            if ($employee) {
                $whatsapp = app(EmployeeTodoNotifier::class)->notifyTemplateAssigned(
                    $employee,
                    $weekStart,
                    $addedCount,
                    $template->name
                );
            }
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Template assigned.',
            'redirect' => route('employee-todos.index', [
                'week'     => $weekStart->toDateString(),
                'employee' => $data['employee_id'],
            ]),
            'whatsapp' => $whatsapp,
        ]);
    }

    public function copyWeek(Request $request)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'week'        => ['required', 'date'],
            'employee_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $targetStart = EmployeeWeeklyPlan::normalizeWeekStart($data['week']);
        $sourceStart = $targetStart->copy()->subWeek();

        $sourcePlan = EmployeeWeeklyPlan::where('business_id', $this->businessId())
            ->where('employee_id', $data['employee_id'])
            ->where('week_start_date', $sourceStart->toDateString())
            ->first();

        if (! $sourcePlan || $sourcePlan->items()->count() === 0) {
            return back()->withErrors(['week' => 'No tasks found in the previous week for this employee.']);
        }

        $targetPlan = $this->getOrCreateEmployeePlan((int) $data['employee_id'], $targetStart);

        DB::transaction(function () use ($sourcePlan, $targetPlan) {
            $targetPlan->items()->delete();

            foreach ($sourcePlan->items()->orderBy('sort_order')->get() as $src) {
                EmployeeWeeklyPlanItem::create([
                    'employee_weekly_plan_id' => $targetPlan->id,
                    'category_id'             => $src->category_id,
                    'day_of_week'             => $src->day_of_week,
                    'title'                   => $src->title,
                    'task_time'               => $src->task_time,
                    'checklist_count'         => $src->checklist_count,
                    'allocated_minutes'       => max(1, (int) ($src->allocated_minutes ?: 60)),
                    'completed_count'         => 0,
                    'is_completed'            => false,
                    'completed_at'            => null,
                    'started_at'              => null,
                    'ended_at'                => null,
                    'status'                  => 'pending',
                    'performance_tier'        => null,
                    'earned_star'             => false,
                    'early_start'             => false,
                    'source'                  => $src->source,
                    'sort_order'              => $src->sort_order,
                ]);
            }

            $targetPlan->update([
                'notes'       => $sourcePlan->notes,
                'template_id' => $sourcePlan->template_id,
            ]);
        });

        return redirect()
            ->route('employee-todos.index', ['week' => $targetStart->toDateString(), 'employee' => $data['employee_id']])
            ->with('success', 'Previous week copied. All tasks reset to pending.');
    }

    public function updatePlanNotes(Request $request)
    {
        $data = $request->validate([
            'week'        => ['required', 'date'],
            'employee_id' => ['required', 'integer', 'exists:users,id'],
            'notes'       => ['nullable', 'string', 'max:2000'],
        ]);

        if (! $this->canManage() && (int) $data['employee_id'] !== auth()->id()) {
            abort(403);
        }

        $plan = $this->getOrCreateEmployeePlan(
            (int) $data['employee_id'],
            EmployeeWeeklyPlan::normalizeWeekStart($data['week'])
        );
        $plan->update(['notes' => $data['notes'] ?? null]);

        return back()->with('success', 'Week notes saved.');
    }

    private function authorizeItem(EmployeeWeeklyPlanItem $item, bool $manageOnly = false): void
    {
        $item->loadMissing('plan');
        if ((int) $item->plan->business_id !== $this->businessId()) {
            abort(404);
        }

        if ($manageOnly && ! $this->canManage()) {
            abort(403);
        }

        if (! $this->canManage() && (int) $item->plan->employee_id !== auth()->id()) {
            abort(403);
        }
    }

    private function assertEmployeeInBusiness(int $userId): void
    {
        $exists = User::where('business_id', $this->businessId())
            ->where('id', $userId)->user()->exists();
        if (! $exists) {
            abort(422, 'Invalid employee.');
        }
    }

    private function assertCategory(int $categoryId): void
    {
        $exists = TaskCategory::forBusiness($this->businessId())->where('id', $categoryId)->exists();
        if (! $exists) {
            abort(422, 'Invalid category.');
        }
    }

    private function itemPayload(EmployeeWeeklyPlanItem $item, EmployeeWeeklyPlan $plan): array
    {
        $item->setRelation('plan', $plan);

        return app(EmployeeTodoPerformance::class)->itemToArray($item);
    }

    private function statsPayload(EmployeeWeeklyPlan $plan): array
    {
        $plan->load('items');
        $perf = app(EmployeeTodoPerformance::class);
        $week = $plan->completionStats();
        $days = [];
        foreach (EmployeeWeeklyPlan::dayLabels() as $d => $label) {
            $dayItems = $plan->items->where('day_of_week', $d);
            $total    = $dayItems->count();
            $done     = $dayItems->where('is_completed', true)->count();
            $days[$d] = [
                'total'   => $total,
                'done'    => $done,
                'percent' => $total > 0 ? round(($done / $total) * 100) : 0,
            ];
        }

        return [
            'week'   => $week,
            'days'   => $days,
            'badges' => $perf->weekBadgeStats($plan->items),
            'status' => $perf->myStatus($plan->items),
        ];
    }
}
