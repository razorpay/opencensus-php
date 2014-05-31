<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIinsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('iins', function(Blueprint $table)
		{
			$table->integer('iin')->primary();
			$table->string('card_category', 26);
			$table->string('brand', 10);
			$table->string('card_type', 6);
			$table->string('country_code', 2);
			$table->string('bank', 100);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('iins');
	}

}
