<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('inventory_purchase_payments')) {
            return;
        }

        Schema::table('inventory_purchase_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_purchase_payments', 'cheque_number')) {
                $table->string('cheque_number', 60)->nullable()->after('method');
            }
            if (! Schema::hasColumn('inventory_purchase_payments', 'reference_no')) {
                $table->string('reference_no', 80)->nullable()->after('cheque_number');
            }
            if (! Schema::hasColumn('inventory_purchase_payments', 'attachment')) {
                $table->string('attachment')->nullable()->after('reference_no');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('inventory_purchase_payments')) {
            return;
        }

        Schema::table('inventory_purchase_payments', function (Blueprint $table) {
            foreach (['attachment', 'reference_no', 'cheque_number'] as $column) {
                if (Schema::hasColumn('inventory_purchase_payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
