<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductionTables extends Migration
{
    public function up()
    {
        // Main production job
        Schema::create('production_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->unique();   // e.g. PRN-2026-0001
            $table->unsignedBigInteger('inquiry_id')->nullable(); // linked WhatsApp inquiry
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->string('title');                 // short job title
            $table->text('description')->nullable();
            $table->string('google_drive_url')->nullable();
            $table->enum('current_stage', ['design', 'production', 'quality', 'dispatch', 'completed'])
                  ->default('design');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->date('due_date')->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamps();

            $table->foreign('inquiry_id')->references('id')->on('whatsapp_chat_assignments')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // Files attached to a job (uploaded docs, logos, designs)
        Schema::create('production_job_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->string('original_name');
            $table->string('file_path');             // local storage path
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // bytes
            $table->string('label')->nullable();      // e.g. "Logo", "Design Brief"
            $table->unsignedInteger('uploaded_by');
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('production_jobs')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
        });

        // Stage history — each time a job is moved to the next stage
        Schema::create('production_job_stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->enum('stage', ['design', 'production', 'quality', 'dispatch', 'completed']);
            $table->text('notes')->nullable();
            $table->unsignedInteger('moved_by');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();

            $table->foreign('job_id')->references('id')->on('production_jobs')->onDelete('cascade');
            $table->foreign('moved_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('production_job_stages');
        Schema::dropIfExists('production_job_files');
        Schema::dropIfExists('production_jobs');
    }
}
