<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_flow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('whatsapp_flows')->cascadeOnDelete();
            // Unique within a flow, e.g. "main_menu", "ask_order_id"
            $table->string('step_key');
            // Supports {{variable}} placeholders substituted from conversation variables
            $table->text('message_text');
            $table->enum('step_type', ['menu', 'text_input', 'final'])->default('text_input');
            // For menu type: [{ "label": "1. Check Order", "match": "1", "next_step_key": "ask_order_id" }]
            $table->json('options')->nullable();
            // For text_input/final: where to go next (null = end flow)
            $table->string('next_step_key')->nullable();
            // For text_input: variable name to store the user's raw reply under
            $table->string('save_input_as')->nullable();
            // Explicit first-step marker — cleaner than inferring from references
            $table->boolean('is_first_step')->default(false);
            // Optional: a step (typically a menu option target) can flag a handoff to a human
            $table->boolean('triggers_human_takeover')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['flow_id', 'step_key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_flow_steps');
    }
};
