<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMediaColumnsToWhatsappMessagesTable extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->string('media_type')->nullable()->after('message');       // image|document|video|audio
            $table->string('media_path')->nullable()->after('media_type');    // storage path
            $table->string('media_filename')->nullable()->after('media_path');
            $table->string('media_mimetype')->nullable()->after('media_filename');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn(['media_type', 'media_path', 'media_filename', 'media_mimetype']);
        });
    }
}
