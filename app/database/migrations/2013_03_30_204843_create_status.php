<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatus extends Migration {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('status', function(Blueprint $table){
			$table->engine = 'InnoDB';

			$table->integer('code')
				  ->unsigned()
				  ->primary();

			$table->string('description', 500);
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