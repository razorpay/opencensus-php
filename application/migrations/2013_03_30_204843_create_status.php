<?php

class Create_Status {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('status', function($table){
			$table->engine = 'InnoDB';

			$table->integer('code')
				->unsigned()
				->primary();

			$table->string('description');
		});
	}

	/**
	 * Revert the changes to the database.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('status');
	}

}