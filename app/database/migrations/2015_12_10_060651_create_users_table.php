<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';
            
            $table->char('id', 14)->primary();
            
            $table->string('name', 200);
            
            $table->string('email', 255)->unique();
            
            $table->string('password', 100);
            
            $table->string('remember_token', 100)->nullable();
            
            $table->string('confirm_token')->nullable();
            
            $table->char('current_merchant_id', 14)->nullable();
            
            $table->integer('created_at');
            $table->integer('updated_at');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('users');
	}
}
