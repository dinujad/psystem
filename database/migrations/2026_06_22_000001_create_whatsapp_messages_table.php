<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->enum('direction', ['in', 'out']);
            $table->string('phone_number', 20);
            $table->text('message');
            $table->enum('status', ['sent', 'failed', 'received']);
            $table->string('message_id')->nullable();
            $table->timestamps();

            $table->index(['direction', 'created_at']);
            $table->index('phone_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
