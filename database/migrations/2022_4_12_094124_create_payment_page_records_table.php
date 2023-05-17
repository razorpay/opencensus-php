<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Constants\Table;
use RZP\Models\PaymentLink\PaymentPageRecord\Entity;

class CreatePaymentPageRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYMENT_PAGE_RECORD, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::PAYMENT_LINK_ID, Entity::ID_LENGTH);

            $table->char(Entity::BATCH_ID, Entity::ID_LENGTH);

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::PRIMARY_REFERENCE_ID, 50);

            $table->string(Entity::EMAIL, 255)
                  ->nullable();

            $table->string(Entity::CONTACT, 255)
                  ->nullable();

            $table->json(Entity::OTHER_DETAILS);

            $table->bigInteger(Entity::AMOUNT)
                  ->unsigned();

            $table->bigInteger(Entity::TOTAL_AMOUNT)
                  ->unsigned();

            $table->string(Entity::STATUS, 8)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::PAYMENT_LINK_ID);

            $table->index(Entity::PRIMARY_REFERENCE_ID);

            $table->json(Entity::CUSTOM_FIELD_SCHEMA);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::PAYMENT_PAGE_RECORD);
    }
}
