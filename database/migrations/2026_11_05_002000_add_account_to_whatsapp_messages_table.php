<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('whatsapp_messages')) {
            return;
        }

        if (! Schema::hasColumn('whatsapp_messages', 'account')) {
            Schema::table('whatsapp_messages', function (Blueprint $table) {
                $table->string('account', 32)->default('printworks')->after('phone_number');
                $table->index('account');
            });
        }
    }

    public function down()
    {
        if (! Schema::hasTable('whatsapp_messages')) {
            return;
        }

        if (Schema::hasColumn('whatsapp_messages', 'account')) {
            Schema::table('whatsapp_messages', function (Blueprint $table) {
                $table->dropIndex(['account']);
                $table->dropColumn('account');
            });
        }
    }
};
