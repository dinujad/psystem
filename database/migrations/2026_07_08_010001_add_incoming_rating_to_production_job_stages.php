<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIncomingRatingToProductionJobStages extends Migration
{
    public function up()
    {
        Schema::table('production_job_stages', function (Blueprint $table) {
            // Rating (1-5) + comment the section gives to the work received
            // from the PREVIOUS section, captured when starting the task.
            $table->unsignedTinyInteger('incoming_rating')->nullable()->after('quality_comment');
            $table->text('incoming_comment')->nullable()->after('incoming_rating');
            $table->unsignedInteger('rated_by')->nullable()->after('incoming_comment');
        });
    }

    public function down()
    {
        Schema::table('production_job_stages', function (Blueprint $table) {
            $table->dropColumn(['incoming_rating', 'incoming_comment', 'rated_by']);
        });
    }
}
