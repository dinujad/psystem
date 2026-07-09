<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInquiryStatusToWhatsappChatAssignments extends Migration
{
    public function up()
    {
        Schema::table('whatsapp_chat_assignments', function (Blueprint $table) {
            $table->string('inquiry_status')->default('quotation_waiting')->after('inquiry_notes');
            $table->decimal('payment_amount', 15, 2)->nullable()->after('inquiry_status');
            $table->string('payment_method')->nullable()->after('payment_amount');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->unsignedInteger('status_updated_by')->nullable()->after('payment_reference');
            $table->timestamp('status_updated_at')->nullable()->after('status_updated_by');

            $table->foreign('status_updated_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('whatsapp_inquiry_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->decimal('payment_amount', 15, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('updated_by');
            $table->timestamps();

            $table->foreign('assignment_id')->references('id')->on('whatsapp_chat_assignments')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_inquiry_status_logs');

        Schema::table('whatsapp_chat_assignments', function (Blueprint $table) {
            $table->dropForeign(['status_updated_by']);
            $table->dropColumn([
                'inquiry_status', 'payment_amount', 'payment_method',
                'payment_reference', 'status_updated_by', 'status_updated_at',
            ]);
        });
    }
}
