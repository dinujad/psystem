<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWaFieldsToWhatsappContacts extends Migration
{
    public function up()
    {
        Schema::table('whatsapp_contacts', function (Blueprint $table) {
            $table->string('wa_name')->nullable()->after('name');
            $table->string('profile_picture')->nullable()->after('wa_name');
        });
    }

    public function down()
    {
        Schema::table('whatsapp_contacts', function (Blueprint $table) {
            $table->dropColumn(['wa_name', 'profile_picture']);
        });
    }
}
