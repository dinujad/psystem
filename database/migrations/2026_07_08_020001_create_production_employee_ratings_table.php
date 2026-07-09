<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductionEmployeeRatingsTable extends Migration
{
    public function up()
    {
        Schema::create('production_employee_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedInteger('rated_user_id');      // previous-section employee being rated
            $table->string('rated_stage', 30);             // e.g. 'design'
            $table->string('rater_stage', 30);             // e.g. 'production'
            $table->unsignedInteger('rated_by');           // who submitted the rating
            $table->unsignedTinyInteger('rating');         // 1-5
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['job_id', 'rated_stage']);
            $table->index('rated_user_id');
            $table->foreign('job_id')->references('id')->on('production_jobs')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('production_employee_ratings');
    }
}
