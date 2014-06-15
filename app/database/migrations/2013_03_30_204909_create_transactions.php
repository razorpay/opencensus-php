<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Constants\Field\Transaction;
use Constants\Field\Common;

class CreateTransactions  extends Migration {

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TRANSACTION, function(Blueprint $table){
            $table->engine = 'InnoDB';

            $table->char(Transaction::ID, Constants\Fields::ID_LENGTH)
                  ->primary();

            $table->integer(Common::MERCHANT_ID)
                  ->unsigned();

            $table->integer(Transaction::AMOUNT)
                  ->unsigned();

            $table->enum(Transaction::STATUS, array(
                                        'open',
                                        'auth',
                                        'capture_failed',
                                        'captured',
                                        'refunded',
                                        'settlement_sent',
                                        'settled',
                                        'failed'
                                        ));

            $table->char(Transaction::CURRENCY, Constants\Fields::CURRENCY_LENGTH)
                  ->default('INR');

            $table->string(Transaction::DESCRIPTION);

            $table->boolean('livemode');

            $table->char(Transaction::TOKEN, 16)
                  ->unique()
                  ->nullable();

            $table->boolean('hold')
                  ->default('1');

            $table->string('error', 10);

            $table->binary('udf');


            // Adds created_at and updated_at columns to the table
            $table->integer(Common::CREATED_AT);
            $table->integer(Common::UPDATED_AT);

            $table->foreign(Common::MERCHANT_ID)
                  ->references(Constants\Field\Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Transaction::TOKEN)
                  ->references('token')
                  ->on(Table::TOKEN);
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::TRANSACTION, function($table){

            $table->dropForeign('transactions_merchant_id_foreign');

            $table->dropForeign('transactions_token_foreign');
        });

        Schema::drop(Table::TRANSACTION);
    }

}
