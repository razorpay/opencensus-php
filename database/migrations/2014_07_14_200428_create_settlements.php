<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Settlement\Entity as Settlement;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Models\Transaction;
use RZP\Models\Merchant;

class CreateSettlements extends Migration
{
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

            $table->char(Settlement::BANK_ACCOUNT_ID, BankAccount::ID_LENGTH)
                  ->nullable();

            $table->bigInteger(Settlement::AMOUNT)
                  ->unsigned();

            $table->integer(Settlement::FEES)
                  ->unsigned();

            $table->integer(Settlement::TAX)
                  ->unsigned()
                  ->nullable();

            $table->string(Settlement::STATUS);

            $table->char(Settlement::TRANSACTION_ID, Settlement::ID_LENGTH)
                  ->nullable()
                  ->unique();

            $table->char(Settlement::BALANCE_ID, Merchant\Balance\Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Settlement::CHANNEL, 8);

            $table->integer(Settlement::ATTEMPTS)
                  ->default(1);

            $table->string(Settlement::UTR)
                  ->nullable()
                  ->unique();

            $table->string(Settlement::FAILURE_REASON)
                  ->nullable();

            $table->string(Settlement::REMARKS)
                  ->nullable();

            $table->string(Settlement::RETURN_UTR)
                  ->nullable()
                  ->unique();

            $table->integer(Settlement::PROCESSED_AT)
                  ->nullable();

            $table->integer(Settlement::SETTLED_ON)
                  ->nullable();

            $table->boolean(Settlement::IS_NEW_SERVICE)
                  ->default(0);

            // Adds created_at and updated_at columns to the table
            $table->integer(Settlement::CREATED_AT);
            $table->integer(Settlement::UPDATED_AT);

            $table->index(Settlement::CHANNEL);

            $table->index(Settlement::STATUS);

            $table->integer(Settlement::FTS_TRANSFER_ID)
                  ->nullable();

            $table->index(Settlement::CREATED_AT);

            $table->index(Settlement::UPDATED_AT);
            $table->index([Settlement::MERCHANT_ID, Settlement::CREATED_AT]);

            $table->index(Settlement::FTS_TRANSFER_ID);

            $table->index(Settlement::BALANCE_ID);

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
                Table::TRANSACTION.'_'.Transaction\Entity::SETTLEMENT_ID.'_foreign');
        });

        Schema::table(Table::SETTLEMENT, function($table)
        {
            $table->dropForeign(
                Table::SETTLEMENT.'_'.Settlement::TRANSACTION_ID.'_foreign');

            $table->dropForeign(
                Table::SETTLEMENT.'_'.Settlement::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::SETTLEMENT);
    }
}
