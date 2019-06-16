<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Plan\Subscription\SubscriptionTransaction\Entity;
use RZP\Models\Plan\Subscription\Status;

class CreateSubscriptionTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SUBSCRIPTION_TRANSACTION,function(Blueprint $table)
        {
            $table->engine = 'InnoDB';
            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::CYCLE_ID, Entity::ID_LENGTH);
            $table->char(Entity::TYPE, 16);
            $table->integer(Entity::ADDON_AMOUNT)
                ->unsigned();

            $table->integer(Entity::PLAN_AMOUNT)
                ->unsigned();

            $table->integer(Entity::UNUSED_AMOUNT)
                ->unsigned();

            $table->integer(Entity::PAYMENT_AMOUNT)
                ->unsigned();

            $table->integer(Entity::REFUND_AMOUNT)
                ->unsigned();

            $table->char(Entity::INVOICE_ID, Entity::ID_LENGTH)
                ->nullable();

            $table->char(Entity::PAYMENT_ID, Entity::ID_LENGTH)
                ->nullable();

            $table->char(Entity::CREDIT_NOTE_ID, Entity::ID_LENGTH)
                ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);
            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index([Entity::CYCLE_ID, Entity::CREATED_AT]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::SUBSCRIPTION_TRANSACTION);
    }
}
