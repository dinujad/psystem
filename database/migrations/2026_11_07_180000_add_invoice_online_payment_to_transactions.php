<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }
        if (! Schema::hasColumn('transactions', 'invoice_online_payment')) {
            Schema::table('transactions', function (Blueprint $table) {
                // card = OnePay Pay with Card | payzy = BNPL | bank = bank transfer (no online buttons)
                $table->string('invoice_online_payment', 20)->nullable()->after('payment_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'invoice_online_payment')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('invoice_online_payment');
            });
        }
    }
};
