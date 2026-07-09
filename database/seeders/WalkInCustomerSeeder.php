<?php

namespace Database\Seeders;

use App\Business;
use App\Utils\ContactUtil;
use Illuminate\Database\Seeder;

class WalkInCustomerSeeder extends Seeder
{
    /**
     * Restore or create Walk-In Customer for every business.
     */
    public function run(): void
    {
        $contactUtil = new ContactUtil();

        foreach (Business::all() as $business) {
            $contactUtil->ensureWalkInCustomer($business->id, $business->owner_id);
            $this->command?->info("Walk-In Customer ready for business: {$business->name} (ID: {$business->id})");
        }
    }
}
