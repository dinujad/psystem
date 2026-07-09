<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_job_section_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->string('stage', 30);
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['job_id', 'stage']);
            $table->foreign('job_id')->references('id')->on('production_jobs')->cascadeOnDelete();
        });

        Schema::create('production_job_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->string('stage', 30);
            $table->string('title', 200);
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('production_jobs')->cascadeOnDelete();
        });

        Schema::table('production_jobs', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('created_by');
            $table->boolean('customer_notified_at_start')->default(false)->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'customer_notified_at_start']);
        });

        Schema::dropIfExists('production_job_tasks');
        Schema::dropIfExists('production_job_section_plans');
    }
};
