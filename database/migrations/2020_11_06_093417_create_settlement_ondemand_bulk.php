<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Settlement\Ondemand\Bulk\Entity;

class CreateSettlementOndemandBulk extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SETTLEMENT_ONDEMAND_BULK, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::SETTLEMENT_ONDEMAND_TRANSFER_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::SETTLEMENT_ONDEMAND_ID, Entity::ID_LENGTH);

            $table->bigInteger(Entity::AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::ID);

            $table->index(Entity::SETTLEMENT_ONDEMAND_TRANSFER_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::SETTLEMENT_ONDEMAND_BULK);
    }
}
