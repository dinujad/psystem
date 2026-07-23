<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'whatsapp.access', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'whatsapp.agent', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'whatsapp.assign', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        Permission::where('name', 'whatsapp.assign')->delete();
    }
};
