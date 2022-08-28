<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Transaction;
use RZP\Models\Currency\Currency;
use RZP\Models\Settlement\Ondemand\Status;
use RZP\Models\Settlement\Ondemand\Entity;

class CreateSettlementOndemand extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SETTLEMENT_ONDEMAND, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, RZP\Models\Merchant\Entity::ID_LENGTH);

            $table->char(Entity::USER_ID, RZP\Models\User\Entity::ID_LENGTH)
                  ->nullable();

            $table->bigInteger(Entity::AMOUNT)
                  ->unsigned();

            $table->bigInteger(Entity::TOTAL_AMOUNT_SETTLED)
                  ->unsigneds()
                  ->default(0);

            $table->integer(Entity::TOTAL_FEES)
                  ->unsigned()
                  ->default(0);

            $table->integer(Entity::TOTAL_TAX)
                  ->unsigned()
                  ->default(0);

            $table->bigInteger(Entity::TOTAL_AMOUNT_REVERSED)
                  ->unsigned()
                  ->default(0);

            $table->bigInteger(Entity::TOTAL_AMOUNT_PENDING)
                  ->unsigned()
                  ->default(0);

            $table->boolean(Entity::MAX_BALANCE)
                  ->default(false);

            $table->char(Entity::CURRENCY)
                  ->default(Currency::INR);

            $table->string(Entity::STATUS);

            $table->string(Entity::NARRATION, 30)
                  ->nullable();

            $table->text(Entity::NOTES)
                  ->nullable();

            $table->string(Entity::REMARKS)
                  ->nullable();

            $table->char(Entity::TRANSACTION_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::TRANSACTION_TYPE, 255)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->boolean(Entity::SCHEDULED)
                  ->default(false);

            $table->char(Entity::SETTLEMENT_ONDEMAND_TRIGGER_ID)
                  ->nullable();

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
        Schema::dropIfExists(Table::SETTLEMENT_ONDEMAND);
    }
}
