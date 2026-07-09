<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('invoice_layouts')->update(['design' => 'thermal']);
    }

    public function down()
    {
        DB::table('invoice_layouts')->update(['design' => 'classic']);
    }
};
