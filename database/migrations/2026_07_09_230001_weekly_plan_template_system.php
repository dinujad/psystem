<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('name', 80);
            $table->string('color', 20)->default('#7c5cfc');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->cascadeOnDelete();
            $table->index(['business_id', 'is_active', 'sort_order'], 'tc_biz_active_sort_idx');
        });

        Schema::create('weekly_plan_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('weekly_plan_template_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedTinyInteger('day_of_week');
            $table->string('title', 200);
            $table->string('task_time', 10)->nullable();
            $table->unsignedSmallInteger('checklist_count')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('weekly_plan_templates')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('task_categories')->restrictOnDelete();
            $table->index(['template_id', 'category_id', 'day_of_week'], 'wpti_tpl_cat_day_idx');
        });

        Schema::create('employee_weekly_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('employee_id');
            $table->date('week_start_date');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamps();

            $table->unique(['business_id', 'employee_id', 'week_start_date'], 'ewp_biz_emp_week_uq');
            $table->foreign('business_id')->references('id')->on('business')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('template_id')->references('id')->on('weekly_plan_templates')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('employee_weekly_plan_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_weekly_plan_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedTinyInteger('day_of_week');
            $table->string('title', 200);
            $table->string('task_time', 10)->nullable();
            $table->unsignedSmallInteger('checklist_count')->default(1);
            $table->unsignedSmallInteger('completed_count')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->string('source', 20)->default('manual');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('employee_weekly_plan_id')->references('id')->on('employee_weekly_plans')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('task_categories')->restrictOnDelete();
            $table->index(['employee_weekly_plan_id', 'category_id', 'day_of_week'], 'ewpi_plan_cat_day_idx');
        });

        $this->migrateLegacyWeekTodos();
    }

    private function migrateLegacyWeekTodos(): void
    {
        if (! Schema::hasTable('employee_week_plans') || ! Schema::hasTable('employee_week_tasks')) {
            return;
        }

        $businessIds = DB::table('employee_week_plans')->distinct()->pluck('business_id');

        foreach ($businessIds as $businessId) {
            $generalId = DB::table('task_categories')->insertGetId([
                'business_id' => $businessId,
                'name'        => 'General',
                'color'       => '#6b7280',
                'sort_order'  => 999,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $oldPlans = DB::table('employee_week_plans')->where('business_id', $businessId)->get();

            foreach ($oldPlans as $oldPlan) {
                $tasks = DB::table('employee_week_tasks')->where('plan_id', $oldPlan->id)->get();
                $byEmployee = $tasks->groupBy('user_id');

                foreach ($byEmployee as $employeeId => $employeeTasks) {
                    $newPlanId = DB::table('employee_weekly_plans')->insertGetId([
                        'business_id'      => $businessId,
                        'employee_id'      => $employeeId,
                        'week_start_date'  => $oldPlan->week_start,
                        'template_id'      => null,
                        'notes'            => $oldPlan->notes,
                        'created_by'       => $oldPlan->created_by,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);

                    foreach ($employeeTasks as $task) {
                        $isCompleted = $task->status === 'completed';
                        DB::table('employee_weekly_plan_items')->insert([
                            'employee_weekly_plan_id' => $newPlanId,
                            'category_id'             => $generalId,
                            'day_of_week'             => $task->day_of_week,
                            'title'                   => $task->title,
                            'task_time'               => null,
                            'checklist_count'         => 1,
                            'completed_count'         => $isCompleted ? 1 : 0,
                            'is_completed'            => $isCompleted,
                            'completed_at'            => $task->completed_at,
                            'source'                  => 'manual',
                            'sort_order'              => $task->sort_order,
                            'created_at'              => $task->created_at,
                            'updated_at'              => $task->updated_at,
                        ]);
                    }
                }
            }
        }

        Schema::dropIfExists('employee_week_tasks');
        Schema::dropIfExists('employee_week_plans');
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_weekly_plan_items');
        Schema::dropIfExists('employee_weekly_plans');
        Schema::dropIfExists('weekly_plan_template_items');
        Schema::dropIfExists('weekly_plan_templates');
        Schema::dropIfExists('task_categories');
    }
};
