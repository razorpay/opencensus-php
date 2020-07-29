<?php

use RZP\Constants\Table;
use RZP\Models\UpiMandate\Entity;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpiMandatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::UPI_MANDATE, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::CUSTOMER_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::TOKEN_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::ORDER_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::RECEIPT, 40)
                  ->nullable();

            $table->string(Entity::STATUS, 50)
                  ->nullable();

            $table->integer(Entity::MAX_AMOUNT)
                  ->nullable();

            $table->string(Entity::FREQUENCY, 40)
                  ->nullable();

            $table->string(Entity::RECURRING_TYPE, 40);

            $table->integer(Entity::RECURRING_VALUE)
                  ->nullable();

            $table->integer(Entity::START_TIME)
                  ->nullable();

            $table->integer(Entity::END_TIME)
                  ->nullable();

            $table->string(Entity::UMN, 75)
                  ->nullable();

            $table->string(Entity::RRN, 12)
                  ->nullable();

            $table->string(Entity::NPCI_TXN_ID, 50)
                  ->nullable();

            $table->string(Entity::GATEWAY_DATA, 255)
                  ->nullable();

            $table->unsignedInteger(Entity::USED_COUNT)
                  ->nullable();

            $table->tinyInteger(Entity::LATE_CONFIRMED)
                  ->nullable();

            $table->unsignedInteger(Entity::CONFIRMED_AT)
                  ->nullable();

            $table->unsignedInteger(Entity::CREATED_AT);
            $table->unsignedInteger(Entity::UPDATED_AT);

            $table->index(Entity::MERCHANT_ID);
            $table->index(Entity::TOKEN_ID);
            $table->index(Entity::ORDER_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::UPI_MANDATE);
    }
}
