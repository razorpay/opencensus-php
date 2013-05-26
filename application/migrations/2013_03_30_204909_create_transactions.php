<?php

class Create_Transactions {

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function($table){
            $table->engine = 'InnoDB';
            
            $table->increments('id');

            $table->string('token', 32);

            $table->integer('merchant_id')
                  ->unsigned();

            $table->float('amount')
                  ->unsigned();
            
            $table->integer('card_id')
                  ->unsigned()
                  ->nullable();
            
            $table->integer('status_code')
                  ->unsigned()
                  ->nullable();
            
            $table->string('bankresponse')
                  ->nullable();
            
            $table->string('currency')
                  ->nullable()
                  ->default('INR');
            
            $table->timestamps();        // Adds created_at and updated_at columns to the table

        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('transactions');
    }

}