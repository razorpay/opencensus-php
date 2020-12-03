<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Settlement\Ondemand\Transfer\Entity;

class CreateSettlementOndemandTransfer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SETTLEMENT_ONDEMAND_TRANSFER, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::PAYOUT_ID)
                  ->nullable();

            $table->string(Entity::MODE);

            $table->bigInteger(Entity::AMOUNT)
                  ->unsigned();

            $table->string(Entity::STATUS);

            $table->integer(Entity::PROCESSED_AT)
                  ->nullable();

            $table->integer(Entity::REVERSED_AT)
                  ->nullable();

            $table->integer(Entity::LAST_ATTEMPT_AT)
                  ->nullable();

            $table->integer(Entity::ATTEMPTS);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::SETTLEMENT_ONDEMAND_TRANSFER);
    }
}
