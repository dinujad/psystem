<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emwa_api_clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('api_key', 64)->unique();
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['email', 'api_key', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emwa_api_clients');
    }
};
