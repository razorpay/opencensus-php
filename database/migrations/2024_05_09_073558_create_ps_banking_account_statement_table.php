<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Currency\Currency;
use RZP\Models\BankingAccountStatement\Entity;
class CreatePsBankingAccountStatementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ps_banking_account_statement', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::ENTITY_ID, Transaction\Entity::ID_LENGTH)
                ->nullable();

            $table->string(Entity::ENTITY_TYPE, 255)
                ->nullable();

            $table->string(Entity::CHANNEL, 255);

            $table->char(Entity::MERCHANT_ID, Merchant\Entity::ID_LENGTH);

            $table->string(Entity::ACCOUNT_NUMBER, 255);

            $table->string(Entity::TYPE, 255);

            $table->string(Entity::UTR, 255)
                ->nullable();

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

            $table->string(Entity::BANK_TRANSACTION_ID, 255)
                ->nullable();

            $table->bigInteger(Entity::BALANCE);

            $table->char(Entity::BALANCE_CURRENCY, 3)
                ->default(Currency::INR);

            $table->integer(Entity::TRANSACTION_DATE);

            $table->integer(Entity::POSTED_DATE)
                ->nullable();

            $table->string(Entity::GATEWAY_REF_NUMBER, 48);

            $table->char(Entity::BAS_DETAILS_ID, Transaction\Entity::ID_LENGTH)
                ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ps_banking_account_statement');
    }
}
