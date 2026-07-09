<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_week_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->date('week_start');
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamps();

            $table->unique(['business_id', 'week_start']);
            $table->foreign('business_id')->references('id')->on('business')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('employee_week_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_id');
            $table->unsignedInteger('user_id');
            $table->unsignedTinyInteger('day_of_week'); // 1=Mon … 7=Sun
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('pending'); // pending, in_progress, completed
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedInteger('assigned_by');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('employee_week_plans')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['plan_id', 'user_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_week_tasks');
        Schema::dropIfExists('employee_week_plans');
    }
};
