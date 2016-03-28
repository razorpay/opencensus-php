<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveMerchantEmailUniqueIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('merchants', function($table)
        {
            $table->dropUnique('merchants_email_unique');
            $table->index('email');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('merchants', function($table)
        {
    		$table->dropIndex('merchants_email_index');
            $table->unique('email');
        });
	}

}
