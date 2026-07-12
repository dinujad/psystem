<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'quotation_terms')) {
                $table->text('quotation_terms')->nullable()->after('pdf_bank_details');
            }
            if (! Schema::hasColumn('transactions', 'quotation_valid_till')) {
                $table->date('quotation_valid_till')->nullable()->after('quotation_terms');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'quotation_valid_till')) {
                $table->dropColumn('quotation_valid_till');
            }
            if (Schema::hasColumn('transactions', 'quotation_terms')) {
                $table->dropColumn('quotation_terms');
            }
        });
    }
};
