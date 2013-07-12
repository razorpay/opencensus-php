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

			$table->string('token', 16)
				  ->unique();

			$table->integer('merchant_id')
				  ->unsigned();

			$table->integer('amount')
				  ->unsigned();
			
			$table->string('currency', 3)
				  ->default('INR');

			$table->string('description');

			$table->boolean('livemode');

			$table->string('card_token', 16)
				  ->nullable();
			
			$table->boolean('processed')
				  ->default('1');

			$table->integer('status_code')
				  ->unsigned()
				  ->nullable();
			
			$table->string('bankresponse')
				  ->nullable();
			
			$table->timestamps();	// Adds created_at and updated_at columns to the table

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