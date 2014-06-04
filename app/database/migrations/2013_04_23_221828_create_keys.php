<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKeys extends Migration {

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('keys', function(Blueprint $table){
            $table->engine = 'InnoDB';

            $table->string('id', 32)
                  ->primary();
            
            $table->integer('merchant_id')
                  ->unsigned()
                  ->nullable();

            $table->string('secret', 100);

            $table->boolean('live');
                  
            $table->boolean('active');
                  
            // Adds created_at and updated_at columns to the table
            $table->integer('created_at');  
            $table->integer('updated_at');
            
            $table->foreign('merchant_id')
                  ->references('id')
                  ->on('merchants')
                  ->on_delete('restrict');
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('keys');
    }

}