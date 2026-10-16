<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('inventory_purchases') && ! Schema::hasColumn('inventory_purchases', 'payment_status')) {
            Schema::table('inventory_purchases', function (Blueprint $table) {
                $table->string('payment_status', 20)->default('due')->after('total_amount');
            });
        }

        if (! Schema::hasTable('inventory_purchase_payments')) {
            Schema::create('inventory_purchase_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('inventory_purchase_id');
                $table->decimal('amount', 22, 4);
                $table->date('paid_on');
                $table->string('method', 30)->default('cash');
                $table->string('note')->nullable();
                $table->unsignedInteger('created_by');
                $table->timestamps();

                $table->foreign('inventory_purchase_id', 'inv_purch_pay_fk')
                    ->references('id')->on('inventory_purchases')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('inventory_purchase_payments');

        if (Schema::hasTable('inventory_purchases') && Schema::hasColumn('inventory_purchases', 'payment_status')) {
            Schema::table('inventory_purchases', function (Blueprint $table) {
                $table->dropColumn('payment_status');
            });
        }
    }
};
