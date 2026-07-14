<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Keep DB key `production` (= Workshop). Insert new `printing` stage after design.
        $this->alterEnum('production_jobs', 'current_stage', [
            'design', 'printing', 'production', 'quality', 'dispatch', 'completed',
        ], 'design');

        $this->alterEnum('production_job_stages', 'stage', [
            'design', 'printing', 'production', 'quality', 'dispatch', 'completed',
        ]);

        $this->alterEnum('production_stage_employees', 'stage', [
            'design', 'printing', 'production', 'quality', 'dispatch',
        ]);
    }

    public function down()
    {
        // Jobs still in printing would block shrinking the enum — move them back to design first.
        if (Schema::hasTable('production_jobs')) {
            DB::table('production_jobs')->where('current_stage', 'printing')->update(['current_stage' => 'design']);
        }
        if (Schema::hasTable('production_job_stages')) {
            DB::table('production_job_stages')->where('stage', 'printing')->update(['stage' => 'design']);
        }
        if (Schema::hasTable('production_stage_employees')) {
            DB::table('production_stage_employees')->where('stage', 'printing')->delete();
        }

        $this->alterEnum('production_jobs', 'current_stage', [
            'design', 'production', 'quality', 'dispatch', 'completed',
        ], 'design');

        $this->alterEnum('production_job_stages', 'stage', [
            'design', 'production', 'quality', 'dispatch', 'completed',
        ]);

        $this->alterEnum('production_stage_employees', 'stage', [
            'design', 'production', 'quality', 'dispatch',
        ]);
    }

    private function alterEnum(string $table, string $column, array $values, ?string $default = null): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $list = collect($values)->map(fn ($v) => "'".$v."'")->implode(',');
        $defaultSql = $default ? " DEFAULT '{$default}'" : '';

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` ENUM({$list}){$defaultSql}");
    }
};
