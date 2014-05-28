<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangePaymentidToTransactionidInHdfcTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('hdfc', function(Blueprint $table)
		{
			$table->renameColumn('paymentid', 'transactionid');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('hdfc', function(Blueprint $table)
		{
			//
		});
	}

}
