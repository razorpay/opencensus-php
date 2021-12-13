<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use RZP\Models\FundLoadingDowntime\Entity;
use Illuminate\Database\Migrations\Migration;

class CreateFundLoadingDowntimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::FUND_LOADING_DOWNTIMES, function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::TYPE, 50);

            $table->string(Entity::SOURCE, 20);

            $table->string(Entity::CHANNEL, 20);

            $table->string(Entity::MODE, 20);

            $table->integer(Entity::START_TIME);

            $table->integer(Entity::END_TIME)
                  ->nullable();

            $table->text(Entity::DOWNTIME_MESSAGE)
                  ->nullable();

            $table->string(Entity::CREATED_BY, 255);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index([Entity::START_TIME, Entity::END_TIME], 'fund_loading_downtimes_start_time_end_time_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::FUND_LOADING_DOWNTIMES);
    }
}
