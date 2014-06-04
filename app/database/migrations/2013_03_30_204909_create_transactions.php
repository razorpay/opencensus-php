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

            $table->enum('status', array(
                                        'open',
                                        'auth',
                                        'capture_failed',
                                        'captured',
                                        'refunded',
                                        'settlement_sent',
                                        'settled',
                                        'failed'
                                        ));

            $table->string('currency', 3)
                  ->default('INR');

            $table->string('description');

            $table->boolean('livemode');

            $table->string('token', 16)
                  ->unique()
                  ->nullable();

            $table->boolean('hold')
                  ->default('1');

            $table->string('error', 10);

            $table->binary('udf');


            // Adds created_at and updated_at columns to the table
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->foreign('merchant_id')
                  ->references('id')
                  ->on('merchants')
                  ->on_delete('restrict');

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

            $table->dropForeign('transactions_token_foreign');
        });

        Schema::drop('transactions');
    }

}
