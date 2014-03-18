<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHdfcGateway extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('hdfc', function(Blueprint $table){
			$table->engine = 'InnoDB';

			$table->bigInteger('paymentid')
				  ->unsigned();

			$table->string('action', 1);

			$table->string('trackid', 32);

			$table->string('result', 255);

			$table->string('status', 50);

			$table->string('eci', 2);

			$table->string('auth', 6)
				  ->nullable();

			$table->string('ref', 12)
				  ->nullable();

			$table->string('avr', 3)
				  ->nullable();

			$table->string('postdate', 6)
				  ->nullable();

			$table->string('error_text', 255)
				  ->nullable();

			$table->timestamps();

			$table->foreign('trackid')
				  ->references('id')
				  ->on('transactions')
				  ->on_delete('restrict');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('hdfc', function($table){

			$table->dropForeign('hdfc_trackid_foreign');
		});

		Schema::drop('hdfc');
	}

}
