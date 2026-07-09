<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->unique();
            $table->foreignId('current_flow_id')->nullable()
                ->constrained('whatsapp_flows')->nullOnDelete();
            $table->string('current_step_key')->nullable();
            // Collected input, e.g. {"order_id":"12345","name":"Kasun"}
            $table->json('variables')->nullable();
            $table->enum('status', ['bot_active', 'human_takeover', 'completed', 'idle'])
                ->default('idle');
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('last_interaction_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};
