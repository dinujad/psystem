<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EmployeeTodoPermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['employee_todos.manage', 'employee_todos.view'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $adminRoles = Role::where('name', 'like', 'Admin#%')->get();
        foreach ($adminRoles as $role) {
            $role->givePermissionTo(['employee_todos.manage', 'employee_todos.view']);
        }

        // All other business roles can view and complete their own weekly tasks
        $staffRoles = Role::where('name', 'not like', 'Admin#%')
            ->where('name', 'like', '%#%')
            ->get();
        foreach ($staffRoles as $role) {
            $role->givePermissionTo('employee_todos.view');
        }

        $this->command->info('employee_todos permissions granted to admin and staff roles.');
    }
}
