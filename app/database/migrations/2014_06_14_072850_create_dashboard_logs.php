<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDashboardLogs extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dashboard_logs', function(Blueprint $table){
            $table->engine = 'InnoDB';

            $table->increments(Dashboard\Logs::ID);

            $table->string(Dashboard\Logs::JSON, 1000);

            $table->integer(Dashboard\Logs::CREATED_AT);
            $table->integer(Dashboard\Logs::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('dashboard_logs');
    }

}
