<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWhatsappNumberToProductionStageEmployees extends Migration
{
    public function up()
    {
        Schema::table('production_stage_employees', function (Blueprint $table) {
            $table->string('whatsapp_number', 20)->nullable()->after('user_id');
        });
    }

    public function down()
    {
        Schema::table('production_stage_employees', function (Blueprint $table) {
            $table->dropColumn('whatsapp_number');
        });
    }
}
