<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Plan\Subscription\Version\Entity;
use RZP\Models\Plan\Subscription\Status;

class CreateSubscriptionVersions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SUBSCRIPTION_VERSION, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::SUBSCRIPTION_ID, Entity::ID_LENGTH);

            $table->char(Entity::TOKEN_ID, Entity::ID_LENGTH)
                ->nullable();

            $table->char(Entity::PLAN_ID, Entity::ID_LENGTH);

            $table->char(Entity::SCHEDULE_ID, Entity::ID_LENGTH);

            $table->string(Entity::STATUS, 16)
                ->default(Status::CREATED);

            $table->integer(Entity::QUANTITY)
                ->default(1);

            $table->tinyInteger(Entity::CUSTOMER_NOTIFY)
                ->default(1);

            $table->integer(Entity::TOTAL_COUNT);

            $table->integer(Entity::CANCEL_AT)
                ->nullable();

            $table->integer(Entity::START_AT)
                ->nullable();

            $table->integer(Entity::END_AT)
                ->nullable();

            $table->char(Entity::CYCLE_ID, Entity::ID_LENGTH)
                ->nullable();

            $table->integer(Entity::CYCLE_NUMBER)
                ->nullable();

            $table->char(Entity::CURRENT_PAYMENT_ID, Entity::ID_LENGTH)
                ->nullable();

            $table->integer(Entity::CURRENT_START)
                ->nullable();

            $table->integer(Entity::CURRENT_END)
                ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);
            $table->index(Entity::CANCEL_AT);
            $table->index(Entity::START_AT);
            $table->index(Entity::END_AT);
            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index(Entity::SCHEDULE_ID);
            $table->index(Entity::TOKEN_ID);
            $table->index(Entity::PLAN_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::SUBSCRIPTION_VERSION);
    }
}
