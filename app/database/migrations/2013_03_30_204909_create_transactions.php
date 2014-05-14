<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactions  extends Migration {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('transactions', function(Blueprint $table){
			$table->engine = 'InnoDB';
			
			$table->string('id', 32)
				  ->primary();

			$table->integer('merchant_id')
				  ->unsigned();

			$table->integer('amount')
				  ->unsigned();
			
			$table->string('status', 10);

			$table->string('currency', 3)
				  ->default('INR');

			$table->string('desc');

			$table->boolean('livemode');

			$table->string('token', 16)
				  ->unique()
				  ->nullable();
			
			$table->boolean('processed')
				  ->default('0');

			$table->boolean('refunded')
				  ->default('0');

			$table->integer('status_code')
				  ->unsigned()
				  ->nullable();
			
			$table->string('bankresponse')
				  ->nullable();

			$table->binary('udf');
			
			$table->timestamps();	// Adds created_at and updated_at columns to the table

			$table->foreign('merchant_id')
				  ->references('id')
				  ->on('merchants')
				  ->on_delete('restrict');

  			$table->foreign('status_code')
				  ->references('code')
				  ->on('status')
				  ->on_delete('SET NULL');

			$table->foreign('token')
				  ->references('token')
				  ->on('cardtokens');
		});
	}

	/**
	 * Revert the changes to the database.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('transactions', function($table){

			$table->dropForeign('transactions_merchant_id_foreign');
		
			$table->dropForeign('transactions_status_code_foreign');	

			$table->dropForeign('transactions_token_foreign');
		});

		Schema::drop('transactions');
	}

}