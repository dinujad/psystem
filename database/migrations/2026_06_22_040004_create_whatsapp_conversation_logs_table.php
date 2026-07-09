<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_conversation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $table->enum('direction', ['in', 'out']);
            $table->text('message');
            // Which step this message was sent from (null for free-form / inbound)
            $table->string('step_key')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_conversation_logs');
    }
};
