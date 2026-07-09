<?php

namespace App\Http\Controllers;

use App\TaskCategory;
use Illuminate\Http\Request;

class TaskCategoryController extends Controller
{
    use Concerns\EmployeeTodoAccess;

    public function index()
    {
        $this->authorizeManage();

        $categories = TaskCategory::forBusiness($this->businessId())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('employee-todos.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $maxSort = TaskCategory::forBusiness($this->businessId())->max('sort_order');

        $category = TaskCategory::create([
            'business_id' => $this->businessId(),
            'name'        => trim($data['name']),
            'color'       => $data['color'] ?? '#7c5cfc',
            'sort_order'  => ($maxSort ?? 0) + 1,
            'is_active'   => true,
        ]);

        return response()->json(['success' => true, 'category' => $category]);
    }

    public function update(Request $request, TaskCategory $category)
    {
        $this->authorizeManage();
        $this->authorizeCategory($category);

        $data = $request->validate([
            'name'      => ['sometimes', 'string', 'max:80'],
            'color'     => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order'=> ['sometimes', 'integer', 'min:0'],
        ]);

        $category->update($data);

        return response()->json(['success' => true, 'category' => $category->fresh()]);
    }

    public function destroy(TaskCategory $category)
    {
        $this->authorizeManage();
        $this->authorizeCategory($category);

        if ($category->isInUse()) {
            $category->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Category is in use and was deactivated instead of deleted.',
                'deactivated' => true,
                'category' => $category->fresh(),
            ]);
        }

        $category->delete();

        return response()->json(['success' => true, 'message' => 'Category deleted.']);
    }

    public function reorder(Request $request)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach ($data['order'] as $sort => $id) {
            TaskCategory::forBusiness($this->businessId())
                ->where('id', $id)
                ->update(['sort_order' => $sort + 1]);
        }

        return response()->json(['success' => true]);
    }

    private function authorizeCategory(TaskCategory $category): void
    {
        if ((int) $category->business_id !== $this->businessId()) {
            abort(404);
        }
    }
}
