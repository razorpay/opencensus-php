<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Dasboard\DasboardDal;

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

            $table->increments('id');

            $table->string('json', 1000);

            $table->integer(DasboardDal::CREATED_AT);
            $table->integer(DasboardDal::UPDATED_AT);
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
