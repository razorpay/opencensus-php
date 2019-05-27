<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\MerchantAccount\Entity;

class CreateMerchantAccountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchant_account', function (Blueprint $table) {

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::ACCOUNT_IFSC_CODE, 11)
                  ->nullable();

            $table->string(Entity::ACCOUNT_NUMBER, 40)
                  ->nullable();

            $table->string(Entity::STATUS)
                  ->nullable();

            $table->string(Entity::BANK);

            $table->char(Entity::PINCODE, Entity::PINCODE_LENGTH);

            $table->char(Entity::FTS_FUND_ACCOUNT_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::BALANCE_ID, Entity::ID_LENGTH)
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
        Schema::dropIfExists('merchant_account');
    }
}
