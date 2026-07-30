<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'description_format')) {
                // product_and_note = product name + line note (default)
                // note_only = line description text only (no product name)
                $table->string('description_format', 32)->default('product_and_note')->after('document_brand');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'description_format')) {
                $table->dropColumn('description_format');
            }
        });
    }
};
