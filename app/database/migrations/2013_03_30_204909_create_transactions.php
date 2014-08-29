<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Transaction\Entity as Transaction;
use Models\Merchant;
use Models\Card;
use Models\Ledger;

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

            $table->char(Transaction::MERCHANT_ID, Transaction::ID_LENGTH);

            $table->integer(Transaction::AUTH_AMOUNT)
                  ->unsigned()
                  ->default(0);

            $table->integer(Transaction::AMOUNT)
                  ->unsigned();

            $table->enum(Transaction::STATUS, array(
                                        'open',
                                        'authorized',
                                        'captured',
                                        'refunded',
                                        'failed'
                                        ));

            $table->char(Transaction::CURRENCY, Transaction::CURRENCY_LENGTH);

            $table->string(Transaction::DESCRIPTION)
                  ->nullable();

            $table->char(Transaction::CARD_ID, Transaction::ID_LENGTH)
                  ->nullable();

            $table->string(Transaction::ERROR_CODE, 20)
                  ->nullable();

            $table->string(Transaction::ERROR_DESCRIPTION, 100)
                  ->nullable();

            $table->string(Transaction::EMAIL, 255)
                  ->nullable();

            $table->string(Transaction::CONTACT, 20);

            $table->binary(Transaction::UDF);

            $table->string(Transaction::LEDGER_ID, Transaction::ID_LENGTH)
                  ->unique()
                  ->nullable();

            $table->integer(Transaction::CAPTURED_AT)
                  ->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer(Transaction::CREATED_AT);
            $table->integer(Transaction::UPDATED_AT);

            $table->foreign(Transaction::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Transaction::CARD_ID)
                  ->references(Card\Entity::ID)
                  ->on(Table::CARD)
                  ->on_delete('restrict');
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::TRANSACTION, function($table)
        {
            $table->dropForeign(Table::TRANSACTION.'_'.Transaction::CARD_ID.'_foreign');

            $table->dropForeign(Table::TRANSACTION.'_'.Transaction::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::TRANSACTION);
    }
}
