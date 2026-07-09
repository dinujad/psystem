<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('credit_report2')) {
            Schema::create('credit_report2', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->unsignedInteger('invoice_no');
                $table->string('customer_name', 200);
                $table->string('tp', 20)->nullable();
                $table->decimal('bill_amount', 15, 2);
            });
        }

        if (DB::table('credit_report2')->count() === 0) {
            $sqlPath = database_path('sql/credit_report2_import.sql');

            if (file_exists($sqlPath)) {
                $sql = file_get_contents($sqlPath);
                $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
                DB::unprepared($sql);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_report2');
    }
};
