<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * Creates the two WhatsApp agent permissions.
 *
 *  - send_notifications  : already used for the full admin WhatsApp access
 *  - whatsapp.agent      : limited access — only assigned / unassigned chats, cannot
 *                          access bot flows, labels management, or broadcast.
 *
 * Run once: php artisan db:seed --class=WhatsappAgentPermissionSeeder
 */
class WhatsappAgentPermissionSeeder extends Seeder
{
    public function run()
    {
        Permission::firstOrCreate(['name' => 'whatsapp.agent', 'guard_name' => 'web']);

        $this->command->info('whatsapp.agent permission created (or already existed).');
        $this->command->info('Go to User Management → Roles to assign it to agent users.');
    }
}
