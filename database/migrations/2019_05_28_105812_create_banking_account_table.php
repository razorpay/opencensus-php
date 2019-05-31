<?php

use RZP\Models\BankingAccount\Entity;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBankingAccountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banking_account', function (Blueprint $table) {

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::ACCOUNT_IFSC_CODE, Entity::ACCOUNT_IFSC_CODE_LENGTH)
                ->nullable();

            $table->string(Entity::ACCOUNT_NUMBER, Entity::ACCOUNT_NUMBER_LENGTH)
                ->nullable();

            $table->string(Entity::STATUS, 255)
                ->nullable();

            $table->string(Entity::BANK, 255);

            $table->char(Entity::PINCODE, Entity::PINCODE_LENGTH);

            $table->char(Entity::FTS_FUND_ACCOUNT_ID, Entity::ID_LENGTH)
                ->nullable();

            $table->char(Entity::BALANCE_ID, Entity::ID_LENGTH)
                ->nullable();

            $table->char(Entity::BANK_REFERENCE_NUMBER, 5);

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
        Schema::dropIfExists('banking_account');
    }
}
