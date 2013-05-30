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
	        
	        $table->foreign('card_id')
	        	  ->references('id')
	        	  ->on('cards')
	        	  ->on_delete('SET NULL');
	        
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
			$table->drop_foreign('transactions_card_id_foreign');
			$table->drop_foreign('transactions_status_code_foerign');
		});
	}

}