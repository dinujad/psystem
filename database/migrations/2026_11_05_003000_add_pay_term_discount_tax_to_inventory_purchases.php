<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('inventory_purchases')) {
            Schema::table('inventory_purchases', function (Blueprint $table) {
                if (! Schema::hasColumn('inventory_purchases', 'pay_term_number')) {
                    $table->integer('pay_term_number')->nullable()->after('purchase_date');
                }
                if (! Schema::hasColumn('inventory_purchases', 'pay_term_type')) {
                    $table->string('pay_term_type', 20)->nullable()->after('pay_term_number');
                }
                if (! Schema::hasColumn('inventory_purchases', 'discount_type')) {
                    $table->string('discount_type', 20)->nullable()->after('status');
                }
                if (! Schema::hasColumn('inventory_purchases', 'discount_amount')) {
                    $table->decimal('discount_amount', 22, 4)->default(0)->after('discount_type');
                }
                if (! Schema::hasColumn('inventory_purchases', 'tax_id')) {
                    $table->unsignedInteger('tax_id')->nullable()->after('discount_amount');
                }
                if (! Schema::hasColumn('inventory_purchases', 'tax_amount')) {
                    $table->decimal('tax_amount', 22, 4)->default(0)->after('tax_id');
                }
                if (! Schema::hasColumn('inventory_purchases', 'total_before_tax')) {
                    $table->decimal('total_before_tax', 22, 4)->nullable()->after('tax_amount');
                }
            });
        }

        if (Schema::hasTable('inventory_purchase_payments')
            && ! Schema::hasColumn('inventory_purchase_payments', 'account_id')) {
            Schema::table('inventory_purchase_payments', function (Blueprint $table) {
                $table->unsignedInteger('account_id')->nullable()->after('method');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('inventory_purchases')) {
            Schema::table('inventory_purchases', function (Blueprint $table) {
                foreach (['pay_term_number', 'pay_term_type', 'discount_type', 'discount_amount', 'tax_id', 'tax_amount', 'total_before_tax'] as $col) {
                    if (Schema::hasColumn('inventory_purchases', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('inventory_purchase_payments')
            && Schema::hasColumn('inventory_purchase_payments', 'account_id')) {
            Schema::table('inventory_purchase_payments', function (Blueprint $table) {
                $table->dropColumn('account_id');
            });
        }
    }
};
