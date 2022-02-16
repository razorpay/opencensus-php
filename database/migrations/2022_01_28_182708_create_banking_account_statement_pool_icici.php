<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Currency\Currency;
use RZP\Models\BankingAccountStatement\Pool\Base\Entity;

class CreateBankingAccountStatementPoolIcici extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BANKING_ACCOUNT_STATEMENT_POOL_ICICI, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Merchant\Entity::ID_LENGTH);

            $table->string(Entity::ACCOUNT_NUMBER, 48);

            $table->string(Entity::BANK_TRANSACTION_ID, 255);

            $table->string(Entity::TYPE, 16);

            $table->string(Entity::UTR, 255)
                  ->nullable();

            $table->bigInteger(Entity::AMOUNT)
                  ->unsigned();

            $table->char(Entity::CURRENCY, 3)
                  ->default(Currency::INR);

            $table->string(Entity::DESCRIPTION, 255)
                  ->nullable();

            $table->string(Entity::CATEGORY, 48)
                  ->nullable();

            $table->string(Entity::BANK_SERIAL_NUMBER, 48)
                  ->nullable();

            $table->string(Entity::BANK_INSTRUMENT_ID, 16)
                  ->nullable();

            $table->bigInteger(Entity::BALANCE);

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

            $table->index([Entity::MERCHANT_ID, Entity::CREATED_AT],
            'bas_pool_icici_merchant_id_created_at_index');

            $table->index([Entity::BANK_TRANSACTION_ID, Entity::TRANSACTION_DATE],
            'bas_pool_icici_bank_txn_id_txn_date');

            $table->index(Entity::UTR);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::BANKING_ACCOUNT_STATEMENT_POOL_ICICI);
    }
}
