<?php

class Create_Merchants {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('merchants', function($table){
			$table->engine = 'InnoDB';

			$table->increments('id');

			$table->string('email', 256)
				  ->unique();

			$table->string('pwd');	// For storing passwords in string form. *Not for production*

			$table->string('hash'); // For storing passwords after encrypting them.

			$table->timestamps();
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