<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMerchants extends Migration {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('merchants', function(Blueprint $table){
			$table->engine = 'InnoDB';

			$table->increments('id');

			$table->string('email', 255)
				  ->unique();

			$table->string('pwd', 50);	// For storing passwords in string form. *Not for production*

			$table->string('hash', 100); // For storing passwords after encrypting them.

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