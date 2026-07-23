<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'document_brand')) {
                $table->string('document_brand', 32)->default('printworks')->after('is_quotation');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'document_brand')) {
                $table->dropColumn('document_brand');
            }
        });
    }
};
