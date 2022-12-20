<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\QrPayment\Entity;

class CreateQrPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::QR_PAYMENT, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::QR_CODE_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::PAYMENT_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::PAYER_BANK_ACCOUNT_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::PAYER_VPA, 255)
                  ->nullable();

            $table->string(Entity::GATEWAY)
                  ->nullable();

            $table->tinyInteger(Entity::EXPECTED)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::UNEXPECTED_REASON)
                  ->nullable()
                  ->default(null);

            $table->bigInteger(Entity::AMOUNT)
                  ->unsigned();

            $table->string(Entity::METHOD)
                  ->nullable(false);

            $table->string(Entity::MERCHANT_REFERENCE)
                  ->nullable(true);

            $table->string(Entity::PROVIDER_REFERENCE_ID)
                  ->nullable(true);

            $table->string(Entity::TRANSACTION_TIME)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

             $table->string(Entity::NOTES)
                   ->nullable(true)
                   ->default(null);

            $table->index(Entity::QR_CODE_ID);

            $table->index([Entity::PROVIDER_REFERENCE_ID, Entity::GATEWAY]);

            $table->index(Entity::PAYMENT_ID);

            $table->index(Entity::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::QR_PAYMENT);
    }
}
