<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Essentials\Database\Seeders\WeekdaySaturdayShiftSeeder;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('essentials_shifts', function (Blueprint $table) {
            if (! Schema::hasColumn('essentials_shifts', 'working_days')) {
                $table->text('working_days')->nullable()->after('holidays');
            }
        });

        (new WeekdaySaturdayShiftSeeder())->run();
    }

    public function down(): void
    {
        Schema::table('essentials_shifts', function (Blueprint $table) {
            if (Schema::hasColumn('essentials_shifts', 'working_days')) {
                $table->dropColumn('working_days');
            }
        });
    }
};
