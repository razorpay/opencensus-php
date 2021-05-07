<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\QrPaymentRequest\Entity;
use RZP\Models\BharatQr\Entity as BharatQr;

class CreateQrPaymentRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::QR_PAYMENT_REQUEST, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::QR_CODE_ID)
                  ->nullable();

            $table->string(Entity::BHARAT_QR_ID, BharatQr::ID_LENGTH)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::UPI_ID, 14)
                  ->nullable()
                  ->default(null);

            $table->tinyInteger(Entity::IS_CREATED)
                  ->nullable()
                  ->default(null);

            $table->tinyInteger(Entity::EXPECTED)
                  ->nullable()
                  ->default(null);

            $table->string(Entity::TRANSACTION_REFERENCE)
                  ->nullable(false);

            $table->string(Entity::FAILURE_REASON)
                  ->nullable()
                  ->default(null);

            $table->json(Entity::REQUEST_SOURCE)
                  ->nullable()
                  ->default(null);

            $table->json(Entity::REQUEST_PAYLOAD)
                  ->nullable()
                  ->default(null);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::QR_CODE_ID);

            $table->index(Entity::BHARAT_QR_ID);

            $table->index(Entity::UPI_ID);

            $table->index(Entity::TRANSACTION_REFERENCE);

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
        Schema::dropIfExists(Table::QR_PAYMENT_REQUEST);
    }
}
