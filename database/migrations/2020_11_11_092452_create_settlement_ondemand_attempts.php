<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Settlement\Ondemand\Attempt\Entity;
use RZP\Constants\Table;

class CreateSettlementOndemandAttempts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SETTLEMENT_ONDEMAND_ATTEMPT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::SETTLEMENT_ONDEMAND_TRANSFER_ID, Entity::ID_LENGTH);

            $table->char(Entity::PAYOUT_ID)
                  ->nullable();

            $table->string(Entity::STATUS);

            $table->string(Entity::FAILURE_REASON)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::SETTLEMENT_ONDEMAND_TRANSFER_ID, 'settlement_ondemand_transfer_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::SETTLEMENT_ONDEMAND_ATTEMPT);
    }
}
