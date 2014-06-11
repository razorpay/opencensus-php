<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMerchants extends Migration {

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchants', function(Blueprint $table){
            $table->engine = 'InnoDB';

            $table->integer('id')
                  ->unsigned()
                  ->primary();

            $table->integer('balance')
                  ->default(0);

            $table->integer('created_at');  
            $table->integer('updated_at');
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('merchants');
    }

}