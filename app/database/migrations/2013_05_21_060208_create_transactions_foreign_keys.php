<?php

class Create_Transactions_Foreign_Keys {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('transactions', function($table){

			$table->foreign('merchant_id')
				  ->references('id')
				  ->on('merchants')
				  ->on_delete('restrict');
			
			$table->foreign('token')
				  ->references('token')
				  ->on('cardtokens');
			
			$table->foreign('status_code')
				  ->references('code')
				  ->on('status')
				  ->on_delete('SET NULL');
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
			$table->drop_foreign('transactions_merchant_id_foreign');
			$table->drop_foreign('transactions_token_foreign');
			$table->drop_foreign('transactions_status_code_foreign');
		});
	}

}