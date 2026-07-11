<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_parcels', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('transaction_id')->nullable()->index();
            $table->string('order_id', 100)->nullable()->index();
            $table->string('waybill_no', 50)->nullable()->index();
            $table->string('waybill_mode', 20)->default('new'); // new | existing
            $table->string('parcel_weight', 50);
            $table->text('parcel_description');
            $table->string('recipient_name');
            $table->string('recipient_contact_1', 30);
            $table->string('recipient_contact_2', 30)->nullable();
            $table->text('recipient_address');
            $table->string('recipient_city', 100);
            $table->decimal('amount', 22, 4)->default(0);
            $table->unsignedTinyInteger('exchange')->default(0);
            $table->string('current_status')->nullable()->index();
            $table->timestamp('last_update_time')->nullable();
            $table->unsignedSmallInteger('api_status_code')->nullable();
            $table->json('api_response')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_parcels');
    }
};
