<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Transaction\Refund\Entity as Refund;
use Models\Transaction;
use Models\Merchant;
use Models\Ledger;

class CreateRefunds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::REFUND, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Refund::ID, Refund::ID_LENGTH)
                  ->primary();

            $table->char(Refund::TRANSACTION_ID, Refund::ID_LENGTH);

            $table->char(Refund::MERCHANT_ID, Refund::ID_LENGTH);

            $table->integer(Refund::AMOUNT);

            $table->char(Refund::CURRENCY, Transaction\Entity::CURRENCY_LENGTH);

            $table->char(Refund::LEDGER_ID, Refund::ID_LENGTH)
                  ->unique()
                  ->nullable();

            $table->integer(Refund::CREATED_AT);
            $table->integer(Refund::UPDATED_AT);

            $table->foreign(Refund::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Refund::TRANSACTION_ID)
                  ->references(Transaction\Entity::ID)
                  ->on(Table::TRANSACTION)
                  ->on_delete('restrict');

            $table->foreign(Refund::LEDGER_ID)
                  ->references(Ledger\Entity::ID)
                  ->on(Table::LEDGER)
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::REFUND, function($table)
        {
            $table->dropForeign(Table::REFUND.'_'.Refund::LEDGER_ID.'_foreign');

            $table->dropForeign(Table::REFUND.'_'.Refund::TRANSACTION_ID.'_foreign');

            $table->dropForeign(Table::REFUND.'_'.Refund::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::REFUND);
    }
}
