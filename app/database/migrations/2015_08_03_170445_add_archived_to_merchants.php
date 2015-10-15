<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddArchivedToMerchants extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('merchants', function($table)
        {
            $table->integer('archived_at')
                  ->nullable()
                  ->default(null);;

            $table->index('archived_at');
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
            $table->dropColumn('archived_at');
        });
	}

}
