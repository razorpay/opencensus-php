<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpgradeSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sessions', function($table)
        {
            // This is for our default sessions table
            $table->char('user_id', 14)->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sessions', function($table)
        {
            // This is for our default sessions table
            $table->dropColumn('user_id');
            $table->dropColumn('ip_address');
            $table->dropColumn('user_agent');
        });
    }
}
