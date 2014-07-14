<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Transaction\Entity as Transaction;
use Models\Merchant;
use Models\Token;

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

            $table->char(Transaction::ID, Transaction::ID_LENGTH)
                  ->primary();

            $table->integer(Transaction::MERCHANT_ID)
                  ->unsigned();

            $table->integer(Transaction::AUTH_AMOUNT)
                  ->unsigned()
                  ->default(0);

            $table->integer(Transaction::AMOUNT)
                  ->unsigned();

            $table->enum(Transaction::STATUS, array(
                                        'open',
                                        'auth',
                                        'captured',
                                        'refunded',
                                        'failed'
                                        ));

            $table->char(Transaction::CURRENCY, Transaction::CURRENCY_LENGTH);

            $table->string(Transaction::DESCRIPTION);

            $table->char(Transaction::TOKEN_ID, Transaction::ID_LENGTH)
                  ->unique()
                  ->nullable();

            $table->string(Transaction::ERROR_CODE, 20)
                  ->nullable();

            $table->string(Transaction::ERROR_DESCRIPTION, 100)
                  ->nullable();

            $table->string(Transaction::EMAIL, 255)
                  ->nullable();

            $table->string(Transaction::CONTACT, 20);

            $table->binary(Transaction::UDF);

            // Adds created_at and updated_at columns to the table
            $table->integer(Transaction::CREATED_AT);
            $table->integer(Transaction::UPDATED_AT);

            $table->foreign(Transaction::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Transaction::TOKEN_ID)
                  ->references(Token\Entity::ID)
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

            $table->dropForeign(Table::TRANSACTION.'_'.Transaction::MERCHANT_ID.'_foreign');

            $table->dropForeign(Table::TRANSACTION.'_'.Transaction::TOKEN_ID.'_foreign');
        });

        Schema::drop(Table::TRANSACTION);
    }
}
