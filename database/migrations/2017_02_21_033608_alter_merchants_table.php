<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMerchantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('merchants', function($table) {
            $table->dropColumn('password');
            $table->dropColumn('confirm_token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('merchants', function($table) {
            $table->string('password', 100)->after('activated');
            $table->string('confirm_token')->nullable()->after('remember_token');
        });
    }
}
