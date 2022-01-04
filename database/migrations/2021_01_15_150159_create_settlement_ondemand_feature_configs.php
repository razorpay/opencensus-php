<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Settlement\Ondemand\FeatureConfig\Entity;

class CreateSettlementOndemandFeatureConfigs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SETTLEMENT_ONDEMAND_FEATURE_CONFIG, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->integer(Entity::PERCENTAGE_OF_BALANCE_LIMIT)
                  ->unsigned();

            $table->integer(Entity::SETTLEMENTS_COUNT_LIMIT)
                  ->unsigned();

            $table->bigInteger(Entity::MAX_AMOUNT_LIMIT)
                  ->unsigned();

            $table->integer(Entity::PRICING_PERCENT)
                  ->unsigned()
                  ->nullable();

            $table->integer(Entity::ES_PRICING_PERCENT)
                  ->unsigned()
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::MERCHANT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::SETTLEMENT_ONDEMAND_FEATURE_CONFIG);
    }
}
