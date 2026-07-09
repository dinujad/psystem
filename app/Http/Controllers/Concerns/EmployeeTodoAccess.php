<?php

namespace App\Http\Controllers\Concerns;

trait EmployeeTodoAccess
{
    protected function businessId(): int
    {
        return (int) request()->session()->get('user.business_id');
    }

    protected function canManage(): bool
    {
        $user = auth()->user();

        return $user->can('send_notifications')
            || $user->can('employee_todos.manage')
            || $user->hasRole('Admin#'.$this->businessId());
    }

    protected function authorizeManage(): void
    {
        if (! $this->canManage()) {
            abort(403);
        }
    }
}
