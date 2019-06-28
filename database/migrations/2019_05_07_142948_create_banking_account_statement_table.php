<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Currency\Currency;
use RZP\Models\BankingAccountStatement\Entity;


class CreateBankingAccountStatementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BANKING_ACCOUNT_STATEMENT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::TRANSACTION_ID, Transaction\Entity::ID_LENGTH);

            $table->char(Entity::ENTITY_ID, Transaction\Entity::ID_LENGTH);

            $table->string(Entity::ENTITY_TYPE, 255);

            $table->string(Entity::CHANNEL, 255);

            $table->char(Entity::MERCHANT_ID, Merchant\Entity::ID_LENGTH);

            $table->string(Entity::ACCOUNT_NUMBER, 255);

            $table->string(Entity::BANK_TRANSACTION_ID, 255);

            $table->string(Entity::TYPE, 255);

            $table->bigInteger(Entity::AMOUNT)
                  ->unsigned();

            $table->char(Entity::CURRENCY, 3)
                  ->default(Currency::INR);

            $table->string(Entity::DESCRIPTION, 255)
                  ->nullable();

            $table->string(Entity::CATEGORY, 255)
                  ->nullable();

            $table->string(Entity::BANK_SERIAL_NUMBER, 255)
                  ->nullable();

            $table->string(Entity::BANK_INSTRUMENT_ID, 255)
                  ->nullable();

            $table->bigInteger(Entity::BALANCE)
                  ->unsigned();

            $table->char(Entity::BALANCE_CURRENCY, 3)
                  ->default(Currency::INR);

            $table->integer(Entity::TRANSACTION_DATE);

            $table->integer(Entity::POSTED_DATE)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            // Indexes

            $table->index(Entity::ACCOUNT_NUMBER);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::UPDATED_AT);

            $table->index(Entity::MERCHANT_ID);

            $table->index(Entity::BANK_TRANSACTION_ID);

            $table->index(Entity::ENTITY_ID);

            $table->index(Entity::TRANSACTION_ID);

            $table->index([Entity::MERCHANT_ID, Entity::CREATED_AT]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::BANKING_ACCOUNT_STATEMENT);
    }
}
