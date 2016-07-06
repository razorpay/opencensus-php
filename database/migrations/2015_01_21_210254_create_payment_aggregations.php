<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentAggregations extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('payment_aggregations', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';
            
            $table->char('merchant_id', 14);

            $table->integer('CARD')
                  ->unsigned()
                  ->default(0);

            $table->integer('NETBANKING')
                  ->unsigned()
                  ->default(0);

            $table->integer('AMEX')
                  ->unsigned()
                  ->default(0);

            $table->integer('DICL')
                  ->unsigned()
                  ->default(0);

            $table->integer('DISC')
                  ->unsigned()
                  ->default(0);

            $table->integer('JCB')
                  ->unsigned()
                  ->default(0);

            $table->integer('MAES')
                  ->unsigned()
                  ->default(0);

            $table->integer('MC')
                  ->unsigned()
                  ->default(0);

            $table->integer('RUPAY')
                  ->unsigned()
                  ->default(0);

            $table->integer('VISA')
                  ->unsigned()
                  ->default(0);

            $table->integer('UNP')
                  ->unsigned()
                  ->default(0);

            $table->integer('UNKNOWN')
                  ->unsigned()
                  ->default(0);

            $table->char('mode', 4);

            $table->integer('created_at');
            $table->integer('updated_at');

            $table->foreign('merchant_id')
                  ->references('id')
                  ->on('merchants');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('payment_aggregations', function(Blueprint $table)
      {
          $table->dropForeign('payment_aggregations_merchant_id_foreign');
      });

      Schema::drop('payment_aggregations');
	}

}
