<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryTables extends Migration
{
    public function up()
    {
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_units', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // Pieces, Kilograms, Meters…
            $table->string('abbreviation');   // pcs, kg, m…
            $table->timestamps();
        });

        Schema::create('inventory_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->decimal('price_per_unit', 12, 2)->default(0);
            $table->decimal('current_stock', 14, 3)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('inventory_categories')->onDelete('set null');
            $table->foreign('unit_id')->references('id')->on('inventory_units')->onDelete('set null');
        });

        Schema::create('production_job_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->string('stage')->default('production');
            $table->unsignedBigInteger('material_id');
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_price', 12, 2);   // price snapshot at time of use
            $table->unsignedInteger('added_by');
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('production_jobs')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('inventory_materials')->onDelete('restrict');
            $table->foreign('added_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('production_job_materials');
        Schema::dropIfExists('inventory_materials');
        Schema::dropIfExists('inventory_units');
        Schema::dropIfExists('inventory_categories');
    }
}
