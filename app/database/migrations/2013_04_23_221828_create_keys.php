<?php

class Create_Keys {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('keys', function($table){
			$table->engine = 'InnoDB';

			$table->increments('id');

			$table->string('keys', 32)
				  ->unique();
			
			$table->integer('merchant_id')
				  ->unsigned()
				  ->nullable();

			$table->boolean('secret');

			$table->boolean('live');
				  
			$table->boolean('active');
				  
			$table->timestamps();    // Adds created_at and updated_at columns to the table

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
		//
	}

}