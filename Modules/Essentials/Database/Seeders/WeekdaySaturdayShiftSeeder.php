<?php

namespace Modules\Essentials\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WeekdaySaturdayShiftSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('essentials_shifts') || ! Schema::hasColumn('essentials_shifts', 'working_days')) {
            return;
        }

        $businessIds = DB::table('business')->pluck('id');
        foreach ($businessIds as $businessId) {
            $existing = DB::table('essentials_shifts')->where('business_id', $businessId)->get();
            $names = $existing->map(function ($shift) {
                return strtolower(trim($shift->name));
            });
            if ($existing->isNotEmpty() && ! $names->contains('saturday') && ! $names->contains('weekday')) {
                continue;
            }

            $weekdayId = $this->upsertShift($businessId, [
                'name' => 'Weekday',
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'holidays' => json_encode(['sunday']),
                'working_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
            ]);
            $saturdayId = $this->upsertShift($businessId, [
                'name' => 'Saturday',
                'start_time' => '09:00:00',
                'end_time' => '14:00:00',
                'holidays' => null,
                'working_days' => json_encode(['saturday']),
            ]);

            $this->assignUsers($businessId, [$weekdayId, $saturdayId]);
        }
    }

    private function upsertShift($businessId, array $values)
    {
        $shift = DB::table('essentials_shifts')
            ->where('business_id', $businessId)
            ->whereRaw('LOWER(name) = ?', [strtolower($values['name'])])
            ->first();

        if ($shift) {
            DB::table('essentials_shifts')->where('id', $shift->id)->update([
                'working_days' => $values['working_days'],
                'holidays' => $values['holidays'],
                'updated_at' => now(),
            ]);

            return $shift->id;
        }

        $row = [
            'name' => $values['name'],
            'type' => 'fixed_shift',
            'business_id' => $businessId,
            'start_time' => $values['start_time'],
            'end_time' => $values['end_time'],
            'holidays' => $values['holidays'],
            'working_days' => $values['working_days'],
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('essentials_shifts', 'ot_rate_per_hour')) {
            $row['ot_rate_per_hour'] = 0;
        }

        return DB::table('essentials_shifts')->insertGetId($row);
    }

    private function assignUsers($businessId, array $shiftIds)
    {
        $userIds = DB::table('users')->where('business_id', $businessId)->pluck('id');
        foreach ($userIds as $userId) {
            foreach ($shiftIds as $shiftId) {
                $exists = DB::table('essentials_user_shifts')
                    ->where('user_id', $userId)
                    ->where('essentials_shift_id', $shiftId)
                    ->exists();
                if ($exists) {
                    continue;
                }

                DB::table('essentials_user_shifts')->insert([
                    'user_id' => $userId,
                    'essentials_shift_id' => $shiftId,
                    'start_date' => '2020-01-01',
                    'end_date' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
