<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayzyPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('payzy_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('transaction_id')->nullable()->index();
            $table->unsignedInteger('transaction_payment_id')->nullable()->index();
            $table->string('invoice_no')->nullable();
            $table->string('order_id')->unique();
            $table->decimal('base_amount', 22, 4);
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->decimal('charge_amount', 22, 4);
            $table->unsignedTinyInteger('installment_count')->default(4);
            $table->string('status', 40)->default('pending')->index();
            $table->string('response_code', 20)->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->boolean('is_refunded')->default(false);
            $table->text('refund_note')->nullable();
            $table->timestamp('paid_on')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('return_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payzy_payments');
    }
}
