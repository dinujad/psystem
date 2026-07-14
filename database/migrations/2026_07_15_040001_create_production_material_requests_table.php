<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('production_material_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('material_id');
            $table->decimal('quantity', 22, 4);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedInteger('requested_by');
            $table->text('notes')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->unsignedBigInteger('issued_usage_id')->nullable();
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('production_jobs')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('inventory_materials')->onDelete('restrict');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('issued_usage_id', 'pmr_usage_fk')
                ->references('id')->on('production_job_materials')->onDelete('set null');
            $table->index(['status', 'created_at']);
            $table->index(['job_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('production_material_requests');
    }
};
