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

            $table->string(Entity::BANK_INTERNAL_STATUS, 255)
                  ->nullable();

            $table->string(Entity::CHANNEL, 255);

            $table->char(Entity::PINCODE, Entity::PINCODE_LENGTH);

            $table->char(Entity::FTS_FUND_ACCOUNT_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::BALANCE_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::BANK_INTERNAL_REFERENCE_NUMBER, 255)
                  ->nullable();

            $table->string(Entity::ACCOUNT_NAME, 255)
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

            $table->date(Entity::ACCOUNT_ACTIVATION_DATE)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::BANK_INTERNAL_STATUS);
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
