<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOnepayPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('onepay_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('transaction_id')->nullable()->index();
            $table->unsignedInteger('transaction_payment_id')->nullable()->index();
            $table->string('invoice_no')->nullable();
            $table->string('reference')->index();
            $table->string('ipg_transaction_id')->nullable()->index();
            $table->string('link_id')->nullable();
            $table->string('link_url')->nullable();
            $table->decimal('amount', 22, 4);
            $table->string('currency', 10)->default('LKR');
            $table->string('status', 40)->default('pending')->index();
            $table->string('status_message')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('payment_method', 40)->default('onepay');
            $table->boolean('is_refunded')->default(false);
            $table->decimal('refund_amount', 22, 4)->nullable();
            $table->string('refund_id')->nullable();
            $table->string('refund_reason')->nullable();
            $table->text('refund_note')->nullable();
            $table->timestamp('paid_on')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('callback_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('onepay_payments');
    }
}
