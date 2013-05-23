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
            
            $table->string('id', 32)
                        ->primary();

            $table->integer('merchant_id')
                  ->unsigned();

            $table->integer('amount')
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