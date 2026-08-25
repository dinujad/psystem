<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend transaction edit window from 30 days to 1 year (365 days).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('business', 'transaction_edit_days')) {
            return;
        }

        DB::table('business')
            ->where('transaction_edit_days', '<=', 30)
            ->update(['transaction_edit_days' => 365]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('business', 'transaction_edit_days')) {
            return;
        }

        DB::table('business')
            ->where('transaction_edit_days', 365)
            ->update(['transaction_edit_days' => 30]);
    }
};
