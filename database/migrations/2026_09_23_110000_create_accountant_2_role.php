<?php

use App\Business;
use App\Support\Accountant2Permissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * ACCOUNTANT 2 — full access to Accounting module + related
     * sales (invoice / quotation / proforma), purchases, expenses and accounts.
     */
    public function up(): void
    {
        foreach (Business::query()->pluck('id') as $businessId) {
            Accountant2Permissions::syncRoleForBusiness((int) $businessId);
        }
    }

    public function down(): void
    {
        foreach (Business::query()->pluck('id') as $businessId) {
            $role = Role::where('name', Accountant2Permissions::ROLE_BASE_NAME.'#'.$businessId)->first();
            if ($role) {
                $role->syncPermissions([]);
                $role->delete();
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
