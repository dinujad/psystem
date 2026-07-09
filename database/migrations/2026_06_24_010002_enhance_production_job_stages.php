<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnhanceProductionJobStages extends Migration
{
    public function up()
    {
        Schema::table('production_job_stages', function (Blueprint $table) {
            // Task time tracking (team member clicks start/end work)
            $table->timestamp('task_started_at')->nullable()->after('completed_at');
            $table->timestamp('task_ended_at')->nullable()->after('task_started_at');

            // Rate charged for this stage's work (set when advancing to next)
            $table->decimal('stage_rate', 12, 2)->nullable()->after('task_ended_at');
            $table->text('stage_rate_notes')->nullable()->after('stage_rate');

            // Quality fields — filled by QC team on the PRODUCTION stage record
            $table->unsignedTinyInteger('quality_rating')->nullable()->after('stage_rate_notes'); // 1-5
            $table->text('quality_comment')->nullable()->after('quality_rating');
        });
    }

    public function down()
    {
        Schema::table('production_job_stages', function (Blueprint $table) {
            $table->dropColumn([
                'task_started_at', 'task_ended_at',
                'stage_rate', 'stage_rate_notes',
                'quality_rating', 'quality_comment',
            ]);
        });
    }
}
