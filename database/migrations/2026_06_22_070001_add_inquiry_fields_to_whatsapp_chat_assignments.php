<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInquiryFieldsToWhatsappChatAssignments extends Migration
{
    public function up()
    {
        Schema::table('whatsapp_chat_assignments', function (Blueprint $table) {
            // Drop the unique index so same phone can have multiple history rows
            $table->dropUnique(['phone_number']);
            $table->index('phone_number');

            // Inquiry details captured at close time
            $table->string('customer_name')->nullable()->after('notes');
            $table->string('inquiry_category')->nullable()->after('customer_name');
            $table->text('inquiry_notes')->nullable()->after('inquiry_category');

            // Who closed and when
            $table->unsignedInteger('closed_by')->nullable()->after('inquiry_notes');
            $table->timestamp('closed_at')->nullable()->after('closed_by');

            $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('whatsapp_chat_assignments', function (Blueprint $table) {
            $table->dropForeign(['closed_by']);
            $table->dropColumn(['customer_name', 'inquiry_category', 'inquiry_notes', 'closed_by', 'closed_at']);
            $table->dropIndex(['phone_number']);
            $table->unique('phone_number');
        });
    }
}
