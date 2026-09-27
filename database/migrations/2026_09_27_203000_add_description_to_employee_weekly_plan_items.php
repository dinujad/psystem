<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_weekly_plan_items', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_weekly_plan_items', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
        });

        if (Schema::hasTable('weekly_plan_template_items')
            && ! Schema::hasColumn('weekly_plan_template_items', 'description')) {
            Schema::table('weekly_plan_template_items', function (Blueprint $table) {
                $table->text('description')->nullable()->after('title');
            });
        }
    }

    public function down(): void
    {
        Schema::table('employee_weekly_plan_items', function (Blueprint $table) {
            if (Schema::hasColumn('employee_weekly_plan_items', 'description')) {
                $table->dropColumn('description');
            }
        });

        if (Schema::hasTable('weekly_plan_template_items')
            && Schema::hasColumn('weekly_plan_template_items', 'description')) {
            Schema::table('weekly_plan_template_items', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
