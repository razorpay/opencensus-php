<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Plan\Subscription\UpdateRequest\Entity;
use RZP\Models\Plan\Subscription\Status;

class CreateSubscriptionUpdateRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SUBSCRIPTION_UPDATE_REQUEST, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::SUBSCRIPTION_ID, Entity::ID_LENGTH);

            $table->char(Entity::PLAN_ID, Entity::ID_LENGTH)
                ->nullable();

            $table->integer(Entity::QUANTITY)
                ->nullable();

            $table->tinyInteger(Entity::CUSTOMER_NOTIFY)
                ->nullable();

            $table->integer(Entity::TOTAL_COUNT)
                ->nullable();

            $table->integer(Entity::START_AT)
                ->nullable();

            $table->char(Entity::SCHEDULE_CHANGE_AT)
                ->nullable();

            $table->integer(Entity::VERSION_ID)
                ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::SUBSCRIPTION_UPDATE_REQUEST);
    }
}
