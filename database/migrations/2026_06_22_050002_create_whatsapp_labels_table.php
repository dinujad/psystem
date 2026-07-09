<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappLabelsTable extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_labels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color', 7)->default('#25d366'); // hex color
            $table->timestamps();
        });

        Schema::create('whatsapp_contact_label', function (Blueprint $table) {
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('label_id');
            $table->primary(['contact_id', 'label_id']);
            $table->foreign('contact_id')->references('id')->on('whatsapp_contacts')->onDelete('cascade');
            $table->foreign('label_id')->references('id')->on('whatsapp_labels')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_contact_label');
        Schema::dropIfExists('whatsapp_labels');
    }
}
