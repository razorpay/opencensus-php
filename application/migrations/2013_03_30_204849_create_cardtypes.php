<?php

class Create_Cardtypes {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cardtypes', function($table){
			$table->engine = 'InnoDB';

			$table->increments('id');

			$table->string('type');
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