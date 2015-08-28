<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSbiepay extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('sbiepay', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->char('payment_id', UniqueIdEntity::ID_LENGTH);
            $table->string('action');
            $table->string('method');
            $table->boolean('received')->default(0);
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('sbiepay', function($table)
        {
            $table->dropForeign('sbiepay_payment_id_foreign');
        });

        Schema::drop('sbiepay');
	}

}
