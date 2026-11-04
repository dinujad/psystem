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

        if (! Schema::hasColumn('inventory_purchase_payments', 'cheque_date')) {
            Schema::table('inventory_purchase_payments', function (Blueprint $table) {
                $table->date('cheque_date')->nullable()->after('cheque_number');
            });
        }
    }

    public function down()
    {
        if (! Schema::hasTable('inventory_purchase_payments')) {
            return;
        }

        if (Schema::hasColumn('inventory_purchase_payments', 'cheque_date')) {
            Schema::table('inventory_purchase_payments', function (Blueprint $table) {
                $table->dropColumn('cheque_date');
            });
        }
    }
};
