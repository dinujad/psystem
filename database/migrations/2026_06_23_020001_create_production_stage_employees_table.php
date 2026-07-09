<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductionStageEmployeesTable extends Migration
{
    public function up()
    {
        Schema::create('production_stage_employees', function (Blueprint $table) {
            $table->id();
            $table->enum('stage', ['design', 'production', 'quality', 'dispatch']);
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('assigned_by')->nullable();
            $table->timestamps();

            $table->unique(['stage', 'user_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('production_stage_employees');
    }
}
