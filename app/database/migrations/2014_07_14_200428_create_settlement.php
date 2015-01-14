<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Settlement\Entity as Settlement;
use Models\Transaction;
use Models\Merchant;

class CreateSettlement extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SETTLEMENT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Settlement::ID, Settlement::ID_LENGTH)
                  ->primary();

            $table->char(Settlement::MERCHANT_ID, Settlement::ID_LENGTH);

            $table->integer(Settlement::AMOUNT)
                  ->unsigned();

            $table->string(Settlement::STATUS);

            $table->char(Settlement::TRANSACTION_ID, Settlement::ID_LENGTH)
                  ->nullable()
                  ->unique();

            $table->string(Settlement::UTR)
                  ->nullable()
                  ->unique();

            $table->string(Settlement::FAILURE_REASON)
                  ->nullable();

            $table->string(Settlement::RETURN_UTR)
                  ->nullable()
                  ->unique();

            // Adds created_at and updated_at columns to the table
            $table->integer(Settlement::CREATED_AT);
            $table->integer(Settlement::UPDATED_AT);

            $table->foreign(Settlement::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Settlement::TRANSACTION_ID)
                  ->references(Transaction\Entity::ID)
                  ->on(Table::TRANSACTION)
                  ->on_delete('restrict');
        });

        Schema::table(Table::TRANSACTION, function($table)
        {
            $table->foreign(Transaction\Entity::SETTLEMENT_ID)
                  ->references(Settlement::ID)
                  ->on(Table::SETTLEMENT)
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
        Schema::table(Table::TRANSACTION, function($table)
        {
            $table->dropForeign(
                TABLE::TRANSACTION.'_'.Transaction\Entity::SETTLEMENT_ID.'_foreign');
        });

        Schema::table(Table::SETTLEMENT, function($table)
        {
            $table->dropForeign(
                TABLE::SETTLEMENT.'_'.Settlement::TRANSACTION_ID.'_foreign');

            $table->dropForeign(
                TABLE::SETTLEMENT.'_'.Settlement::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::SETTLEMENT);
    }

}
