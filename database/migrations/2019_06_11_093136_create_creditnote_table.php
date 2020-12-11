<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\CreditNote\Entity;
use RZP\Models\CreditNote\Status;

class CreateCreditNoteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CREDITNOTE, function (Blueprint $table)
        {
            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::CUSTOMER_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::SUBSCRIPTION_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::NAME, 255);

            $table->text(Entity::DESCRIPTION)
                  ->nullable();

            $table->bigInteger(Entity::AMOUNT)
                  ->unsigned();

            $table->bigInteger(Entity::AMOUNT_AVAILABLE)
                  ->unsigned();

            $table->bigInteger(Entity::AMOUNT_REFUNDED)
                  ->unsigned();

            $table->bigInteger(Entity::AMOUNT_ALLOCATED)
                  ->unsigned();

            $table->char(Entity::CURRENCY, 3);

            $table->string(Entity::STATUS, 24)
                  ->default(Status::CREATED);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::MERCHANT_ID);

            $table->index(Entity::SUBSCRIPTION_ID);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::STATUS);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::CREDITNOTE);
    }
}
