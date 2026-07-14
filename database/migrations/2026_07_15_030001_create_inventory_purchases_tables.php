<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inventory_purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('ref_no')->nullable();
            $table->unsignedInteger('contact_id')->nullable(); // supplier
            $table->date('purchase_date');
            $table->enum('status', ['ordered', 'received'])->default('received');
            $table->decimal('total_amount', 22, 4)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['business_id', 'purchase_date']);
        });

        Schema::create('inventory_purchase_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_purchase_id');
            $table->unsignedBigInteger('material_id');
            $table->decimal('quantity', 22, 4);
            $table->decimal('unit_cost', 22, 4);
            $table->decimal('line_total', 22, 4);
            $table->timestamps();

            $table->foreign('inventory_purchase_id', 'inv_purch_lines_purch_fk')
                ->references('id')->on('inventory_purchases')->onDelete('cascade');
            $table->foreign('material_id', 'inv_purch_lines_mat_fk')
                ->references('id')->on('inventory_materials')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_purchase_lines');
        Schema::dropIfExists('inventory_purchases');
    }
};
