<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappChatAssignmentsTable extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_chat_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->unique(); // one active assignment per contact
            $table->unsignedInteger('assigned_to')->nullable();   // agent user id (users.id is int unsigned)
            $table->unsignedInteger('assigned_by')->nullable();   // admin who assigned
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_chat_assignments');
    }
}
