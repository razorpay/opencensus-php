<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Settlement\OndemandPayout\Entity;

class CreateSettlementOndemandPayoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SETTLEMENT_ONDEMAND_PAYOUT, function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, RZP\Models\Merchant\Entity::ID_LENGTH);

            $table->char(Entity::USER_ID, RZP\Models\User\Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::SETTLEMENT_ONDEMAND_ID, Entity::ID_LENGTH);

            $table->char(Entity::PAYOUT_ID)
                  ->nullable();

            $table->string(Entity::ENTITY_TYPE)
                  ->nullable();

            $table->string(Entity::MODE)
                  ->nullable();

            $table->integer(Entity::INITIATED_AT)
                  ->nullable();

            $table->integer(Entity::PROCESSED_AT)
                  ->nullable();

            $table->integer(Entity::REVERSED_AT)
                  ->nullable();

            $table->integer(Entity::FEES)
                  ->unsigned()
                  ->nullable();

            $table->integer(Entity::TAX)
                  ->unsigned()
                  ->nullable();

            $table->string(Entity::UTR, 255)
                  ->nullable();

            $table->string(Entity::STATUS);

            $table->bigInteger(Entity::AMOUNT)
                  ->unsigned();

            $table->string(Entity::FAILURE_REASON)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::SETTLEMENT_ONDEMAND_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::SETTLEMENT_ONDEMAND_PAYOUT);
    }
}
