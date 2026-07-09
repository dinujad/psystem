<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskCategorySeeder extends Seeder
{
    public function run(): void
    {
        $businessIds = DB::table('business')->pluck('id');
        $defaults = [
            ['name' => 'Google', 'color' => '#4285F4'],
            ['name' => 'Website', 'color' => '#7c5cfc'],
            ['name' => 'Facebook', 'color' => '#1877F2'],
            ['name' => 'Instagram', 'color' => '#E4405F'],
            ['name' => 'TikTok', 'color' => '#000000'],
            ['name' => 'WhatsApp', 'color' => '#25D366'],
        ];

        foreach ($businessIds as $businessId) {
            $exists = DB::table('task_categories')->where('business_id', $businessId)->exists();
            if ($exists) {
                continue;
            }

            foreach ($defaults as $i => $cat) {
                DB::table('task_categories')->insert([
                    'business_id' => $businessId,
                    'name'        => $cat['name'],
                    'color'       => $cat['color'],
                    'sort_order'  => $i + 1,
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        $this->command->info('Task categories seeded for all businesses.');
    }
}
