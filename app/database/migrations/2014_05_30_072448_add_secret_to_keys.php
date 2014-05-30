<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSecretToKeys extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
  {
      Schema::table('keys', function($table)
      {
					$table->dropColumn('secret');

      });

			Schema::table('keys', function($table)
			{
					$table->string('secret', 100);

			});
  }

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('keys', function($table)
		{
				$table->dropColumn('secret');
		});
	}

}
