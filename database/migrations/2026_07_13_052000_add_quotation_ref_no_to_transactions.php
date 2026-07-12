<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'quotation_ref_no')) {
                $table->string('quotation_ref_no', 191)->nullable()->after('invoice_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'quotation_ref_no')) {
                $table->dropColumn('quotation_ref_no');
            }
        });
    }
};
