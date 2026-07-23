<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * Creates WhatsApp permissions.
 *
 *  - whatsapp.access : full WhatsApp module (menu + admin features)
 *  - whatsapp.agent  : limited access — assigned / unassigned chats, My Inquiries
 *  - whatsapp.assign : can assign chats to other agents (needs agent or access for inbox)
 *
 * Run once: php artisan db:seed --class=WhatsappAgentPermissionSeeder
 */
class WhatsappAgentPermissionSeeder extends Seeder
{
    public function run()
    {
        Permission::firstOrCreate(['name' => 'whatsapp.access', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'whatsapp.agent', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'whatsapp.assign', 'guard_name' => 'web']);

        $this->command->info('whatsapp.access, whatsapp.agent and whatsapp.assign permissions created (or already existed).');
        $this->command->info('Go to User Management → Roles to assign them.');
    }
}
