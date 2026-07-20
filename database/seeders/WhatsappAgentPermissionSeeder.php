<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * Creates the two WhatsApp agent permissions.
 *
 *  - whatsapp.access : full WhatsApp module (menu + admin features)
 *  - whatsapp.agent  : limited access — only assigned / unassigned chats, cannot
 *                      access bot flows, labels management, or broadcast.
 *
 * Run once: php artisan db:seed --class=WhatsappAgentPermissionSeeder
 */
class WhatsappAgentPermissionSeeder extends Seeder
{
    public function run()
    {
        Permission::firstOrCreate(['name' => 'whatsapp.access', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'whatsapp.agent', 'guard_name' => 'web']);

        $this->command->info('whatsapp.access and whatsapp.agent permissions created (or already existed).');
        $this->command->info('Go to User Management → Roles to assign them.');
    }
}
