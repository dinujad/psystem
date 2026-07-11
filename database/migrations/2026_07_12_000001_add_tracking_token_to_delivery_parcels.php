<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('delivery_parcels', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_parcels', 'tracking_token')) {
                $table->string('tracking_token', 64)->nullable()->unique()->after('waybill_no');
            }
            if (! Schema::hasColumn('delivery_parcels', 'status_history')) {
                $table->json('status_history')->nullable()->after('last_update_time');
            }
        });
    }

    public function down()
    {
        Schema::table('delivery_parcels', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_parcels', 'status_history')) {
                $table->dropColumn('status_history');
            }
            if (Schema::hasColumn('delivery_parcels', 'tracking_token')) {
                $table->dropUnique(['tracking_token']);
                $table->dropColumn('tracking_token');
            }
        });
    }
};
