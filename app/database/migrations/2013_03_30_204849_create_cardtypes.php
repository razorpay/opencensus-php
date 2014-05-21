<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCardtypes extends Migration {

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cardtypes', function(Blueprint $table){
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->string('type', 10);
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cardtypes');
    }

}