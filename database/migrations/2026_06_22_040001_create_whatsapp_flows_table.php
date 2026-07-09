<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_flows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // e.g. ["hi","hello","start"] — matched case-insensitively
            $table->json('trigger_keywords')->nullable();
            // Used when no keyword matches and there is no active session
            $table->boolean('is_default_fallback')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('is_default_fallback');
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_flows');
    }
};
