<?php

use App\Business;
use App\ReferenceCount;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $start = max(1, (int) config('constants.proforma_no_start', 650)) - 1;

        Business::query()->select('id')->orderBy('id')->each(function ($business) use ($start) {
            ReferenceCount::firstOrCreate(
                [
                    'ref_type' => 'proforma',
                    'business_id' => $business->id,
                ],
                [
                    'ref_count' => $start,
                ]
            );
        });
    }

    public function down(): void
    {
        ReferenceCount::where('ref_type', 'proforma')->delete();
    }
};
