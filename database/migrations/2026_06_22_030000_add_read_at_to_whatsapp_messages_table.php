<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('status');
        });

        // Existing incoming messages were already seen — don't flood the inbox with badges
        DB::table('whatsapp_messages')
            ->where('direction', 'in')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function down()
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn('read_at');
        });
    }
};
