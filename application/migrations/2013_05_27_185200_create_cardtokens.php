<?php

class Create_Cardtokens {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cardtokens', function($table){
			$table->engine = 'InnoDB';

			$table->increments('id');

			$table->integer('card_id')
				  ->unsigned()
				  ->nullable();

			$table->string('token', 32);

			$table->boolean('expired');

			$table->timestamps();

			$table->foreign('card_id')
				  ->references('id')
				  ->on('cards')
				  ->on_delete('SET NULL');
		});
	}

	/**
	 * Revert the changes to the database.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('cardtokens');
	}

}