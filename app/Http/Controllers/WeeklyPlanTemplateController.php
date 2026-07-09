<?php

namespace App\Http\Controllers;

use App\TaskCategory;
use App\WeeklyPlanTemplate;
use App\WeeklyPlanTemplateItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeeklyPlanTemplateController extends Controller
{
    use Concerns\EmployeeTodoAccess;

    public function index()
    {
        $this->authorizeManage();

        $templates = WeeklyPlanTemplate::where('business_id', $this->businessId())
            ->withCount('items')
            ->orderByDesc('updated_at')
            ->get();

        return view('employee-todos.templates.index', compact('templates'));
    }

    public function create()
    {
        $this->authorizeManage();

        $categories = TaskCategory::forBusiness($this->businessId())->active()->orderBy('sort_order')->get();
        $template   = new WeeklyPlanTemplate(['name' => '', 'description' => '']);
        $days       = $this->daysMeta();
        $builder    = $this->builderPayload($template, $categories, $days);

        return view('employee-todos.templates.edit', compact('template', 'categories', 'days', 'builder'));
    }

    public function store(Request $request)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'items'       => ['nullable', 'array'],
            'items.*.category_id'     => ['required_with:items', 'integer'],
            'items.*.day_of_week'     => ['required_with:items', 'integer', 'min:1', 'max:7'],
            'items.*.title'           => ['required_with:items', 'string', 'max:200'],
            'items.*.task_time'       => ['nullable', 'string', 'max:10'],
            'items.*.checklist_count' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $template = DB::transaction(function () use ($data) {
            $template = WeeklyPlanTemplate::create([
                'business_id' => $this->businessId(),
                'name'        => trim($data['name']),
                'description' => $data['description'] ?? null,
                'created_by'  => auth()->id(),
            ]);

            $this->syncItems($template, $data['items'] ?? []);

            return $template;
        });

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'template_id' => $template->id]);
        }

        return redirect()->route('employee-todos.templates.edit', $template)
            ->with('success', 'Template created.');
    }

    public function edit(WeeklyPlanTemplate $template)
    {
        $this->authorizeManage();
        $this->authorizeTemplate($template);

        $template->load(['items.category']);
        $categories = TaskCategory::forBusiness($this->businessId())->active()->orderBy('sort_order')->get();
        $days       = $this->daysMeta();
        $builder    = $this->builderPayload($template, $categories, $days);

        return view('employee-todos.templates.edit', compact('template', 'categories', 'days', 'builder'));
    }

    public function update(Request $request, WeeklyPlanTemplate $template)
    {
        $this->authorizeManage();
        $this->authorizeTemplate($template);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'items'       => ['nullable', 'array'],
            'items.*.category_id'     => ['required_with:items', 'integer'],
            'items.*.day_of_week'     => ['required_with:items', 'integer', 'min:1', 'max:7'],
            'items.*.title'           => ['required_with:items', 'string', 'max:200'],
            'items.*.task_time'       => ['nullable', 'string', 'max:10'],
            'items.*.checklist_count' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        DB::transaction(function () use ($template, $data) {
            $template->update([
                'name'        => trim($data['name']),
                'description' => $data['description'] ?? null,
            ]);
            $this->syncItems($template, $data['items'] ?? []);
        });

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'template_id' => $template->id]);
        }

        return back()->with('success', 'Template saved.');
    }

    public function destroy(WeeklyPlanTemplate $template)
    {
        $this->authorizeManage();
        $this->authorizeTemplate($template);
        $template->delete();

        return response()->json(['success' => true]);
    }

    public function duplicate(WeeklyPlanTemplate $template)
    {
        $this->authorizeManage();
        $this->authorizeTemplate($template);

        $new = DB::transaction(function () use ($template) {
            $copy = WeeklyPlanTemplate::create([
                'business_id' => $template->business_id,
                'name'        => $template->name.' (Copy)',
                'description' => $template->description,
                'created_by'  => auth()->id(),
            ]);

            foreach ($template->items as $item) {
                WeeklyPlanTemplateItem::create([
                    'template_id'     => $copy->id,
                    'category_id'     => $item->category_id,
                    'day_of_week'     => $item->day_of_week,
                    'title'           => $item->title,
                    'task_time'       => $item->task_time,
                    'checklist_count' => $item->checklist_count,
                    'sort_order'      => $item->sort_order,
                ]);
            }

            return $copy;
        });

        return response()->json(['success' => true, 'template_id' => $new->id, 'redirect' => route('employee-todos.templates.edit', $new)]);
    }

    private function syncItems(WeeklyPlanTemplate $template, array $items): void
    {
        $template->items()->delete();
        $validCategoryIds = TaskCategory::forBusiness($this->businessId())->pluck('id')->all();

        foreach ($items as $idx => $row) {
            if (empty(trim($row['title'] ?? ''))) {
                continue;
            }
            if (! in_array((int) $row['category_id'], $validCategoryIds, true)) {
                continue;
            }

            WeeklyPlanTemplateItem::create([
                'template_id'     => $template->id,
                'category_id'     => (int) $row['category_id'],
                'day_of_week'     => (int) $row['day_of_week'],
                'title'           => trim($row['title']),
                'task_time'       => $row['task_time'] ?? null,
                'checklist_count' => max(1, (int) ($row['checklist_count'] ?? 1)),
                'sort_order'      => $idx + 1,
            ]);
        }
    }

    private function daysMeta(): array
    {
        $days = [];
        foreach (\App\EmployeeWeeklyPlan::dayLabels() as $num => $label) {
            $days[$num] = [
                'label' => $label,
                'short' => \App\EmployeeWeeklyPlan::dayShortLabels()[$num],
            ];
        }

        return $days;
    }

    private function builderPayload(WeeklyPlanTemplate $template, $categories, array $days): array
    {
        $items = [];
        if ($template->exists) {
            foreach ($template->items as $item) {
                $items[] = [
                    'category_id'     => $item->category_id,
                    'day_of_week'     => $item->day_of_week,
                    'title'           => $item->title,
                    'task_time'       => $item->task_time ? substr((string) $item->task_time, 0, 5) : '',
                    'checklist_count' => $item->checklist_count,
                    '_key'            => 'e'.$item->id,
                ];
            }
        }

        $dayList = [];
        foreach ($days as $num => $day) {
            $dayList[] = [
                'num'   => $num,
                'short' => $day['short'],
                'label' => $day['label'],
            ];
        }

        return [
            'name'        => $template->name ?? '',
            'description' => $template->description ?? '',
            'categories'  => $categories->map(fn ($c) => [
                'id'    => $c->id,
                'name'  => $c->name,
                'color' => $c->color,
            ])->values()->all(),
            'days'        => $dayList,
            'items'       => $items,
            'templateId'  => $template->id,
            'saveUrl'     => $template->exists
                ? route('employee-todos.templates.update', $template)
                : route('employee-todos.templates.store'),
            'method'      => $template->exists ? 'PUT' : 'POST',
        ];
    }

    private function authorizeTemplate(WeeklyPlanTemplate $template): void
    {
        if ((int) $template->business_id !== $this->businessId()) {
            abort(404);
        }
    }
}
