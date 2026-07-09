<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_materials', function (Blueprint $table) {
            $table->string('sku', 40)->nullable()->unique()->after('name');
            $table->decimal('reorder_level', 14, 3)->default(0)->after('current_stock');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_materials', function (Blueprint $table) {
            $table->dropColumn(['sku', 'reorder_level']);
        });
    }
};
