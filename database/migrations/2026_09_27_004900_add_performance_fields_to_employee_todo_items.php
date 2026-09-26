<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_weekly_plan_items', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_weekly_plan_items', 'allocated_minutes')) {
                $table->unsignedInteger('allocated_minutes')->default(60)->after('checklist_count');
            }
            if (! Schema::hasColumn('employee_weekly_plan_items', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('completed_at');
            }
            if (! Schema::hasColumn('employee_weekly_plan_items', 'ended_at')) {
                $table->timestamp('ended_at')->nullable()->after('started_at');
            }
            if (! Schema::hasColumn('employee_weekly_plan_items', 'status')) {
                $table->string('status', 20)->default('pending')->after('ended_at');
            }
            if (! Schema::hasColumn('employee_weekly_plan_items', 'performance_tier')) {
                $table->string('performance_tier', 20)->nullable()->after('status');
            }
            if (! Schema::hasColumn('employee_weekly_plan_items', 'earned_star')) {
                $table->boolean('earned_star')->default(false)->after('performance_tier');
            }
            if (! Schema::hasColumn('employee_weekly_plan_items', 'early_start')) {
                $table->boolean('early_start')->default(false)->after('earned_star');
            }
        });

        if (Schema::hasTable('weekly_plan_template_items')
            && ! Schema::hasColumn('weekly_plan_template_items', 'allocated_minutes')) {
            Schema::table('weekly_plan_template_items', function (Blueprint $table) {
                $table->unsignedInteger('allocated_minutes')->default(60)->after('checklist_count');
            });
        }

        // Sync status for existing completed rows
        if (Schema::hasColumn('employee_weekly_plan_items', 'status')) {
            \DB::table('employee_weekly_plan_items')
                ->where('is_completed', 1)
                ->where('status', 'pending')
                ->update(['status' => 'completed']);
        }
    }

    public function down(): void
    {
        Schema::table('employee_weekly_plan_items', function (Blueprint $table) {
            foreach (['allocated_minutes', 'started_at', 'ended_at', 'status', 'performance_tier', 'earned_star', 'early_start'] as $col) {
                if (Schema::hasColumn('employee_weekly_plan_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        if (Schema::hasTable('weekly_plan_template_items')
            && Schema::hasColumn('weekly_plan_template_items', 'allocated_minutes')) {
            Schema::table('weekly_plan_template_items', function (Blueprint $table) {
                $table->dropColumn('allocated_minutes');
            });
        }
    }
};
