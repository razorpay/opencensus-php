<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Constants\Table;
use RZP\Models\Settlement\EarlySettlementFeaturePeriod\Entity;

class CreateEarlySettlementFeaturePeriod extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::EARLY_SETTLEMENT_FEATURE_PERIOD, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                    ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->integer(Entity::ENABLE_DATE);

            $table->integer(Entity::DISABLE_DATE);

            $table->char(Entity::INITIAL_SCHEDULE_ID, Entity::ID_LENGTH);

            $table->integer(Entity::INITIAL_ONDEMAND_PRICING)
                  ->unsigned();

            $table->char(Entity::FEATURE);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

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
        Schema::dropIfExists(Table::EARLY_SETTLEMENT_FEATURE_PERIOD);
    }
}
