<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('essentials_shifts', function (Blueprint $table) {
            if (! Schema::hasColumn('essentials_shifts', 'ot_rate_per_hour')) {
                $table->decimal('ot_rate_per_hour', 22, 4)->default(0)->after('end_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('essentials_shifts', function (Blueprint $table) {
            if (Schema::hasColumn('essentials_shifts', 'ot_rate_per_hour')) {
                $table->dropColumn('ot_rate_per_hour');
            }
        });
    }
};
