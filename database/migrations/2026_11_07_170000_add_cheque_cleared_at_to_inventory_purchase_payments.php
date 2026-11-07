<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_purchase_payments')) {
            return;
        }

        if (! Schema::hasColumn('inventory_purchase_payments', 'cheque_cleared_at')) {
            Schema::table('inventory_purchase_payments', function (Blueprint $table) {
                $table->timestamp('cheque_cleared_at')->nullable()->after('cheque_date');
            });
        }

        // Existing cheque rows were already treated as paid — keep them cleared.
        DB::table('inventory_purchase_payments')
            ->where('method', 'cheque')
            ->whereNull('cheque_cleared_at')
            ->update([
                'cheque_cleared_at' => DB::raw('COALESCE(paid_on, created_at, NOW())'),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_purchase_payments') && Schema::hasColumn('inventory_purchase_payments', 'cheque_cleared_at')) {
            Schema::table('inventory_purchase_payments', function (Blueprint $table) {
                $table->dropColumn('cheque_cleared_at');
            });
        }
    }
};
