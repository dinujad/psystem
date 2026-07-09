<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductConversionToProductionJobs extends Migration
{
    public function up()
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            $table->unsignedInteger('product_id')->nullable()->after('created_by');
            $table->unsignedInteger('variation_id')->nullable()->after('product_id');
            $table->decimal('converted_qty', 22, 4)->nullable()->after('variation_id');
            $table->timestamp('converted_at')->nullable()->after('converted_qty');
            $table->unsignedInteger('converted_by')->nullable()->after('converted_at');
        });
    }

    public function down()
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            $table->dropColumn(['product_id', 'variation_id', 'converted_qty', 'converted_at', 'converted_by']);
        });
    }
}
