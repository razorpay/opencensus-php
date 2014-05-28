<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTypeAndBankColumnsInCardsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('cards', function(Blueprint $table)
		{
			$table->string('type', 6);
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
		Schema::table('cards', function(Blueprint $table)
		{
			$table->dropColumn('type', 'bank');
		});
	}

}
