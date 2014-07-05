<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Constants\Field\Transaction;
use Constants\Field\Common;

class CreateTransactions  extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TRANSACTION, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Transaction::ID, Constants\Fields::ID_LENGTH)
                  ->primary();

            $table->integer(Common::MERCHANT_ID)
                  ->unsigned();

            $table->integer(Transaction::AUTH_AMOUNT)
                  ->unsigned()
                  ->default(0);

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

            $table->char(Transaction::TOKEN_ID, Constants\Fields::ID_LENGTH)
                  ->unique()
                  ->nullable();

            $table->boolean('hold')
                  ->default('1');

            $table->string(Transaction::ERROR_CODE, 20)
                  ->nullable();

            $table->string(Transaction::ERROR_DESCRIPTION, 100)
                  ->nullable();

            $table->string(Transaction::EMAIL, 255)
                  ->nullable();

            $table->string(Transaction::CONTACT, 20);

            $table->binary(Transaction::UDF);

            // Adds created_at and updated_at columns to the table
            $table->integer(Common::CREATED_AT);
            $table->integer(Common::UPDATED_AT);

            $table->foreign(Common::MERCHANT_ID)
                  ->references(Constants\Field\Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Transaction::TOKEN_ID)
                  ->references(Common::ID)
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

            $table->dropForeign(Table::TRANSACTION.'_'.Common::MERCHANT_ID.'_foreign');

            $table->dropForeign(Table::TRANSACTION.'_'.Transaction::TOKEN_ID.'_foreign');
        });

        Schema::drop(Table::TRANSACTION);
    }

}
