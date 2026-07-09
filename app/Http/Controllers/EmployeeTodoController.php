<?php

namespace App\Http\Controllers;

use App\EmployeeWeeklyPlan;
use App\EmployeeWeeklyPlanItem;
use App\Services\EmployeeTodoNotifier;
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
        if (! $this->canManage()) {
            return redirect()->route('employee-todos.my-week', $request->only('week'));
        }

        return $this->weekView($request, false);
    }

    public function myWeek(Request $request)
    {
        if (! $this->isStaffUser()) {
            abort(403, 'You do not have access to To-Do.');
        }

        $request->merge(['employee' => auth()->id()]);

        return $this->weekView($request, true);
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

        $categories = TaskCategory::forBusiness($this->businessId())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $plan  = null;
        $items = collect();
        $dayStats = [];
        $weekStats = ['total' => 0, 'completed' => 0, 'percent' => 0];

        if ($employeeId) {
            $plan = $this->getOrCreateEmployeePlan((int) $employeeId, $weekStart);
            $flatItems = $plan->items()
                ->with('category')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
            $plan->setRelation('items', $flatItems);
            $items = $flatItems->groupBy(fn ($i) => $i->category_id.'_'.$i->day_of_week);

            $weekStats = $plan->completionStats();
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
            'allEmployees', 'categories', 'items', 'days', 'dayStats', 'weekStats',
            'canManage', 'employeeId', 'personalOnly', 'templates', 'selectedEmp'
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
            'week'            => ['required', 'date'],
            'employee_id'     => ['required', 'integer', 'exists:users,id'],
            'category_id'     => ['required', 'integer'],
            'day_of_week'     => ['required', 'integer', 'min:1', 'max:7'],
            'title'           => ['required', 'string', 'max:200'],
            'task_time'       => ['nullable', 'string', 'max:10'],
            'checklist_count' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

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
            'completed_count'         => 0,
            'is_completed'            => false,
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
            'item'     => $this->itemPayload($item, $plan),
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

        $completed = ! $item->is_completed;
        $item->markCompleted($completed);

        return response()->json([
            'success' => true,
            'item'    => $this->itemPayload($item->fresh('category'), $item->plan),
            'stats'   => $this->statsPayload($item->plan->fresh()),
        ]);
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
                    'completed_count'         => 0,
                    'is_completed'            => false,
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
                    'completed_count'         => 0,
                    'is_completed'            => false,
                    'completed_at'            => null,
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
        return [
            'id'              => $item->id,
            'title'           => $item->title,
            'task_time'       => $item->task_time,
            'checklist_count' => $item->checklist_count,
            'is_completed'    => $item->is_completed,
            'completed_at'    => $item->completed_at?->format('d M H:i'),
            'source'          => $item->source,
            'category_id'     => $item->category_id,
            'day_of_week'     => $item->day_of_week,
            'category_color'  => $item->category?->color,
        ];
    }

    private function statsPayload(EmployeeWeeklyPlan $plan): array
    {
        $plan->load('items');
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

        return ['week' => $week, 'days' => $days];
    }
}
