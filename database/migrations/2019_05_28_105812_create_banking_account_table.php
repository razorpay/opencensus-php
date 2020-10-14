<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Currency\Currency;
use RZP\Models\BankingAccount\Entity;

class CreateBankingAccountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BANKING_ACCOUNT, function (Blueprint $table)
        {
            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::ACCOUNT_IFSC, Entity::ACCOUNT_IFSC_LENGTH)
                  ->nullable();

            $table->string(Entity::ACCOUNT_NUMBER, Entity::ACCOUNT_NUMBER_LENGTH)
                  ->nullable();

            $table->string(Entity::STATUS, 255)
                  ->nullable();

            $table->string(Entity::SUB_STATUS, 64)
                  ->nullable();

            $table->string(Entity::BANK_INTERNAL_STATUS, 255)
                  ->nullable();

            $table->string(Entity::CHANNEL, 255);

            $table->string(Entity::PINCODE, 255)
                  ->nullable();

            $table->char(Entity::FTS_FUND_ACCOUNT_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::BALANCE_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->bigInteger(Entity::GATEWAY_BALANCE)
                  ->nullable();

            $table->string(Entity::BANK_INTERNAL_REFERENCE_NUMBER, 255)
                  ->nullable();

            $table->string(Entity::BENEFICIARY_NAME, 255)
                  ->nullable();

            $table->char(Entity::ACCOUNT_CURRENCY, 3)
                  ->default(Currency::INR);

            $table->string(Entity::BENEFICIARY_EMAIL, 255)
                  ->nullable();

            $table->string(Entity::BENEFICIARY_MOBILE, 255)
                  ->nullable();

            $table->string(Entity::BENEFICIARY_CITY, 255)
                  ->nullable();

            $table->string(Entity::BENEFICIARY_STATE, 255)
                  ->nullable();

            $table->string(Entity::BENEFICIARY_COUNTRY, 255)
                  ->nullable();

            $table->string(Entity::BENEFICIARY_ADDRESS1, 255)
                  ->nullable();

            $table->string(Entity::BENEFICIARY_ADDRESS2, 255)
                  ->nullable();

            $table->string(Entity::BENEFICIARY_ADDRESS3, 255)
                  ->nullable();

            $table->string(Entity::BANK_REFERENCE_NUMBER, 255)
                  ->nullable();

            $table->integer(Entity::ACCOUNT_ACTIVATION_DATE)
                  ->nullable();

            $table->string(Entity::USERNAME, 255)
                  ->nullable();

            $table->string(Entity::PASSWORD, 255)
                  ->nullable();

            $table->string(Entity::REFERENCE1, 255)
                  ->nullable();

            $table->string(Entity::BENEFICIARY_PIN, 255)
                  ->nullable();

            $table->string(Entity::ACCOUNT_TYPE, 255);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::LAST_STATEMENT_ATTEMPT_AT)
                  ->unsigned()
                  ->nullable();

            $table->integer(Entity::BALANCE_LAST_FETCHED_AT)
                  ->nullable();

            $table->string(Entity::INTERNAL_COMMENT, 255)
                  ->nullable();

            $table->index(Entity::STATUS);

            $table->index(Entity::CHANNEL);

            $table->index(Entity::BANK_INTERNAL_STATUS);

            $table->index(Entity::BALANCE_ID);

            $table->index(Entity::FTS_FUND_ACCOUNT_ID);

            $table->index(Entity::BANK_REFERENCE_NUMBER);

            $table->index(Entity::ACCOUNT_NUMBER);

            $table->unique([Entity::ACCOUNT_NUMBER, Entity::CHANNEL]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::BANKING_ACCOUNT);
    }
}
