<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_stage_employees', function (Blueprint $table) {
            $table->boolean('is_head')->default(false)->after('whatsapp_number');
        });
    }

    public function down(): void
    {
        Schema::table('production_stage_employees', function (Blueprint $table) {
            $table->dropColumn('is_head');
        });
    }
};
