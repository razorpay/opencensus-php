<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Ledger\Entity as Ledger;
use Models\Merchant;
use Models\Transaction;

class CreateLedger  extends Migration
{

    /**
     * Runs the migration
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::LEDGER, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Ledger::ID, Ledger::ID_LENGTH)
                  ->primary();

            $table->char(Ledger::ENTITY_ID, Ledger::ID_LENGTH);

            $table->string(Ledger::ENTITY_TYPE, 20);

            $table->char(Ledger::MERCHANT_ID, Ledger::ID_LENGTH);

            $table->integer(Ledger::AMOUNT)
                  ->unsigned();

            $table->integer(Ledger::FEE)
                  ->unsigned();

            $table->char(Ledger::PRICING_RULE_ID, Ledger::ID_LENGTH)
                  ->nullable();

            $table->integer(Ledger::DEBIT)
                  ->unsigned();

            $table->integer(Ledger::CREDIT)
                  ->unsigned();

            $table->char(Ledger::CURRENCY, 3);

            $table->integer(Ledger::BALANCE)
                  ->unsigned();

            $table->integer(Ledger::GATEWAY_FEE)
                  ->unsigned();

            $table->integer(Ledger::API_FEE);

            $table->integer(Ledger::ESCROW_BALANCE);

            $table->boolean(Ledger::SETTLED)
                  ->default(0);

            $table->integer(Ledger::SETTLED_AT);

            $table->integer(Ledger::RECONCILED_AT)
                  ->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer(Ledger::CREATED_AT);
            $table->integer(Ledger::UPDATED_AT);

            $table->index(Ledger::ENTITY_ID);

            $table->foreign(Ledger::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });

        Schema::table(Table::TRANSACTION, function(Blueprint $table)
        {
            $table->foreign(Transaction\Entity::LEDGER_ID)
                  ->references(Ledger::ID)
                  ->on(Table::LEDGER)
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
        Schema::table(Table::LEDGER, function($table)
        {
            $table->dropForeign(
                TABLE::LEDGER.'_'.Ledger::MERCHANT_ID.'_foreign');
        });

        Schema::table(Table::TRANSACTION, function($table)
        {
            $table->dropForeign(Table::TRANSACTION.'_'.Transaction\Entity::LEDGER_ID.'_foreign');
        });

        Schema::drop(Table::LEDGER);
    }
}
