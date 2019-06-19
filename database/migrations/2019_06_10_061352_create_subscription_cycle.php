<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Plan\Subscription\Cycle\Entity;
use RZP\Models\Plan\Subscription\Status;

class CreateSubscriptionCycle extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SUBSCRIPTION_CYCLE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::SUBSCRIPTION_ID, Entity::ID_LENGTH);

            $table->char(Entity::PAYMENT_ID, Entity::ID_LENGTH)
                ->nullable();

            $table->char(Entity::INVOICE_ID, Entity::ID_LENGTH)
                ->nullable();

            $table->integer(Entity::INVOICE_AMOUNT)
                ->unsigned()
                ->nullable();

            $table->string(Entity::STATUS, 16)
                ->default(Status::CREATED);

            $table->integer(Entity::AUTH_ATTEMPTS)
                ->default(0);

            $table->integer(Entity::START)
                ->nullable();

            $table->integer(Entity::END)
                ->nullable();

            $table->integer(Entity::CYCLE_NUMBER)
                ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::UPDATED_AT);

            $table->index(Entity::STATUS);

            $table->index([Entity::SUBSCRIPTION_ID, Entity::START]);

            $table->index([Entity::SUBSCRIPTION_ID, Entity::CYCLE_NUMBER]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::SUBSCRIPTION_CYCLE);
    }
}
