<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactions extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->integer('merchant_id')
                  ->unsigned();

            $table->enum('type', array(
                                      'day',
                                      'week',
                                      'month',
                                      'year'
                                      ));

            $table->integer('amount')
                  ->unsigned();

            $table->integer('count')
                  ->unsigned()
                  ->default(1);
                  
            $table->char('mode', 4);

            $table->integer('created_at');
            $table->integer('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('transactions'); 
    }

}
