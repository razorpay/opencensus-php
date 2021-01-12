<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\UpiTransferRequest\Entity;

class CreateUpiTransferRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::UPI_TRANSFER_REQUEST, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::GATEWAY, 20)
                  ->nullable(false);

            $table->tinyInteger(Entity::IS_CREATED)
                  ->nullable()
                  ->default(0);

            $table->string(Entity::ERROR_MESSAGE)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::NPCI_REFERENCE_ID);

            $table->string(Entity::PAYEE_VPA)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::PAYER_VPA)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::PAYER_BANK)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::PAYER_ACCOUNT, 40)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::PAYER_IFSC, 20)
                  ->nullable()
                  ->default(null);

            $table->integer(Entity::AMOUNT)
                  ->nullable()
                  ->default(0);

            $table->string(Entity::GATEWAY_MERCHANT_ID)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::PROVIDER_REFERENCE_ID)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::TRANSACTION_REFERENCE)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::TRANSACTION_TIME)
                  ->nullable()
                  ->default(null);

            $table->json(Entity::REQUEST_PAYLOAD)
                  ->nullable(false);

            $table->json(Entity::REQUEST_SOURCE)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::NPCI_REFERENCE_ID);
            $table->index(Entity::PAYEE_VPA);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::UPI_TRANSFER_REQUEST);
    }
}
