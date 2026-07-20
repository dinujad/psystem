<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up()
    {
        Permission::firstOrCreate(['name' => 'whatsapp.access', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'whatsapp.agent', 'guard_name' => 'web']);
    }

    public function down()
    {
        Permission::where('name', 'whatsapp.access')->delete();
    }
};
