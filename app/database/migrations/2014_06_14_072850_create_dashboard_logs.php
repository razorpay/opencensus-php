<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Field\Common;

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

            $table->integer(Common::CREATED_AT);  
            $table->integer(Common::UPDATED_AT);
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
