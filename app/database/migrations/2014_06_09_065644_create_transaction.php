<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Transaction\Entity as Transaction;
use Models\Merchant;
use Models\Payment;

class CreateTransaction  extends Migration
{

    /**
     * Runs the migration
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

            $table->char(Transaction::ENTITY_ID, Transaction::ID_LENGTH)
                  ->unique();

            $table->string(Transaction::ENTITY_TYPE, 20);

            $table->char(Transaction::MERCHANT_ID, Transaction::ID_LENGTH);

            $table->integer(Transaction::AMOUNT)
                  ->unsigned();

            $table->integer(Transaction::FEE)
                  ->unsigned();

            $table->char(Transaction::PRICING_RULE_ID, Transaction::ID_LENGTH)
                  ->nullable();

            $table->integer(Transaction::DEBIT)
                  ->unsigned();

            $table->integer(Transaction::CREDIT)
                  ->unsigned();

            $table->char(Transaction::CURRENCY, 3);

            $table->integer(Transaction::BALANCE)
                  ->unsigned()
                  ->nullable();

            $table->integer(Transaction::GATEWAY_FEE)
                  ->unsigned()
                  ->nullable();

            $table->integer(Transaction::API_FEE)
                  ->nullable();

            $table->integer(Transaction::ESCROW_BALANCE)
                  ->nullable();

            $table->boolean(Transaction::SETTLED)
                  ->default(0);

            $table->integer(Transaction::SETTLED_AT);

            $table->integer(Transaction::RECONCILED_AT)
                  ->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer(Transaction::CREATED_AT);
            $table->integer(Transaction::UPDATED_AT);

            $table->index(Transaction::ENTITY_ID);

            $table->foreign(Transaction::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });

        Schema::table(Table::PAYMENT, function(Blueprint $table)
        {
            $table->foreign(Payment\Entity::TRANSACTION_ID)
                  ->references(Transaction::ID)
                  ->on(Table::TRANSACTION)
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
            $table->dropForeign(
                TABLE::TRANSACTION.'_'.Transaction::MERCHANT_ID.'_foreign');
        });

        Schema::table(Table::PAYMENT, function($table)
        {
            $table->dropForeign(Table::PAYMENT.'_'.Payment\Entity::TRANSACTION_ID.'_foreign');
        });

        Schema::drop(Table::TRANSACTION);
    }
}
