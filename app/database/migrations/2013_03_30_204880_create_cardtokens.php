<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCardtokens extends Migration {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cardtokens', function(Blueprint $table){
			$table->engine = 'InnoDB';

			$table->increments('id');

			$table->integer('card_id')
				  ->unsigned()
				  ->nullable();

			$table->integer('merchant_id')
				  ->unsigned()
				  ->nullable();

			$table->string('token', 16)
				  ->unique();

			$table->boolean('expired');

			$table->timestamps();

			$table->foreign('card_id')
				  ->references('id')
				  ->on('cards')
				  ->on_delete('SET NULL');

			$table->foreign('merchant_id')
				  ->references('id')
				  ->on('merchants');
		});
	}

	/**
	 * Revert the changes to the database.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('cardtokens', function($table){

			$table->dropForeign('cardtokens_card_id_foreign');
		
			$table->dropForeign('cardtokens_merchant_id_foreign');
		});

		Schema::drop('cardtokens');
	}

}