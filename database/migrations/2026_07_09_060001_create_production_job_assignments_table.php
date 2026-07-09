<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_job_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->string('stage', 30);
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('assigned_by');
            $table->timestamps();

            $table->unique(['job_id', 'stage', 'user_id']);
            $table->foreign('job_id')->references('id')->on('production_jobs')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_job_assignments');
    }
};
