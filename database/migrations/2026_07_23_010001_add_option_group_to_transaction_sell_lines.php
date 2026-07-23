<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('transaction_sell_lines', 'option_group')) {
                $table->unsignedSmallInteger('option_group')->default(1)->after('sell_line_note');
                $table->index(['transaction_id', 'option_group']);
            }
        });
    }

    public function down()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_sell_lines', 'option_group')) {
                $table->dropIndex(['transaction_id', 'option_group']);
                $table->dropColumn('option_group');
            }
        });
    }
};
