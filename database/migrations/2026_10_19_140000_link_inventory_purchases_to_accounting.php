<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('inventory_purchases') && ! Schema::hasColumn('inventory_purchases', 'transaction_id')) {
            Schema::table('inventory_purchases', function (Blueprint $table) {
                $table->unsignedInteger('transaction_id')->nullable()->after('created_by');
                $table->index('transaction_id', 'inv_purchases_transaction_idx');
            });
        }

        if (Schema::hasTable('inventory_purchase_payments') && ! Schema::hasColumn('inventory_purchase_payments', 'transaction_payment_id')) {
            Schema::table('inventory_purchase_payments', function (Blueprint $table) {
                $table->unsignedInteger('transaction_payment_id')->nullable()->after('created_by');
                $table->index('transaction_payment_id', 'inv_purch_pay_tp_idx');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('inventory_purchases') && Schema::hasColumn('inventory_purchases', 'transaction_id')) {
            Schema::table('inventory_purchases', function (Blueprint $table) {
                $table->dropIndex('inv_purchases_transaction_idx');
                $table->dropColumn('transaction_id');
            });
        }

        if (Schema::hasTable('inventory_purchase_payments') && Schema::hasColumn('inventory_purchase_payments', 'transaction_payment_id')) {
            Schema::table('inventory_purchase_payments', function (Blueprint $table) {
                $table->dropIndex('inv_purch_pay_tp_idx');
                $table->dropColumn('transaction_payment_id');
            });
        }
    }
};
