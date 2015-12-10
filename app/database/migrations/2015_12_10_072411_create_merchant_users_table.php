<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMerchantUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('merchant_users', function(Blueprint $table) 
        {
            $table->engine = 'InnoDB';
           
            $table->char('merchant_id', 14)->index();
            $table->char('user_id', 14)->index();
            
            $table->string('role', 25);
            
            $table->foreign('merchant_id')
            	  ->references('id')
            	  ->on('merchants')
            	  ->onDelete('cascade');
            
            $table->foreign('user_id')
            	  ->references('id')
            	  ->on('users')
            	  ->onDelete('cascade');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('merchant_users', function(Blueprint $table) 
        {
            $table->dropForeign('merchant_users_merchant_id_foreign');
            $table->dropForeign('merchant_users_user_id_foreign');
        });

		Schema::drop('merchant_users');
	}
}
