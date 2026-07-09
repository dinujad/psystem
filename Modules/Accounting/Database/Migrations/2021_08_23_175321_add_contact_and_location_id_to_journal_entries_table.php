<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddContactAndLocationIdToJournalEntriesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (Schema::hasColumn('journal_entries', 'client_id')) {
                // Wait, cannot use dropIndex inside if unless we know index exists. Let's do raw statement with a check.
                DB::statement('ALTER TABLE `journal_entries` CHANGE `client_id` `contact_id` INT(10) UNSIGNED NULL DEFAULT NULL;');
            }
            if (Schema::hasColumn('journal_entries', 'branch_id')) {
                DB::statement('ALTER TABLE `journal_entries` CHANGE `branch_id` `location_id` INT(10) UNSIGNED NULL DEFAULT NULL;');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (Schema::hasColumn('journal_entries', 'contact_id')) {
                DB::statement('ALTER TABLE `journal_entries` CHANGE `contact_id` `client_id` INT(10) UNSIGNED NULL DEFAULT NULL;');
            }
            if (Schema::hasColumn('journal_entries', 'location_id')) {
                DB::statement('ALTER TABLE `journal_entries` CHANGE `location_id` `branch_id` INT(10) UNSIGNED NULL DEFAULT NULL;');
            }
        });
    }
}