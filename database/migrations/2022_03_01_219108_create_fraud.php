<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Payment\Fraud\Entity as Entity;

class CreateFraud extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYMENT_FRAUD, function (Blueprint $table)
        {
            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::PAYMENT_ID, Entity::ID_LENGTH)
                  ->nullable(false);

            $table->string(Entity::ARN)
                  ->nullable();

            $table->string(Entity::TYPE, 10)
                  ->nullable();

            $table->string(Entity::SUB_TYPE, 10)
                  ->nullable();

            $table->integer(Entity::AMOUNT)
                  ->nullable(false);

            $table->string(Entity::CURRENCY, 3)
                  ->nullable(false);

            $table->integer(Entity::BASE_AMOUNT)
                  ->nullable(false);

            $table->integer(Entity::REPORTED_TO_RAZORPAY_AT)
                  ->nullable();

            $table->integer(Entity::REPORTED_TO_ISSUER_AT)
                  ->nullable();

            $table->string(Entity::CHARGEBACK_CODE, 10)
                  ->nullable();

            $table->boolean(Entity::IS_ACCOUNT_CLOSED)
                  ->nullable();

            $table->string(Entity::REPORTED_BY, 32)
                  ->nullable(false);

            $table->string(Entity::SOURCE, 50)
                  ->nullable();

            $table->char(Entity::BATCH_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->integer(Entity::CREATED_AT)
                  ->nullable(false);

            $table->integer(Entity::UPDATED_AT)
                  ->nullable(false);

            $table->index(Entity::PAYMENT_ID);

            $table->index(Entity::BATCH_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::PAYMENT_FRAUD);
    }
}
