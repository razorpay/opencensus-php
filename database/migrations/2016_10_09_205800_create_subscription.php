<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Plan\Subscription\Entity;
use RZP\Models\Plan\Subscription\Status;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\Plan;

class CreateSubscription extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SUBSCRIPTION, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::PLAN_ID, Entity::ID_LENGTH);

            $table->char(Entity::CUSTOMER_ID, Entity::ID_LENGTH);

            $table->char(Entity::TOKEN_ID, Entity::ID_LENGTH);

            $table->string(Entity::STATUS, 16)
                  ->default(Status::CREATED);

            $table->integer(Entity::QUANTITY)
                  ->default(1);

            $table->text(Entity::NOTES);

            $table->integer(Entity::START_AT);

            $table->integer(Entity::END_AT);

            $table->integer(Entity::CHARGE_AT)
                  ->nullable();

            $table->integer(Entity::ENDED_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::START_AT);
            $table->index(Entity::END_AT);
            $table->index(Entity::CHARGE_AT);
            $table->index(Entity::ENDED_AT);
            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);

            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Entity::CUSTOMER_ID)
                  ->references(Customer\Entity::ID)
                  ->on(Table::CUSTOMER)
                  ->on_delete('restrict');

            $table->foreign(Entity::PLAN_ID)
                  ->references(Plan\Entity::ID)
                  ->on(Table::PLAN)
                  ->on_delete('restrict');

            $table->foreign(Entity::TOKEN_ID)
                  ->references(Customer\Token\Entity::ID)
                  ->on(Table::TOKEN)
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::SUBSCRIPTION, function($table)
        {
            $table->dropForeign(Table::SUBSCRIPTION . '_' . Entity::MERCHANT_ID . '_foreign');
        });

        Schema::table(Table::SUBSCRIPTION, function($table)
        {
            $table->dropForeign(Table::SUBSCRIPTION . '_' . Entity::CUSTOMER_ID . '_foreign');
        });

        Schema::table(Table::SUBSCRIPTION, function($table)
        {
            $table->dropForeign(Table::SUBSCRIPTION . '_' . Entity::TOKEN_ID . '_foreign');
        });

        Schema::table(Table::SUBSCRIPTION, function($table)
        {
            $table->dropForeign(Table::SUBSCRIPTION . '_' . Entity::PLAN_ID . '_foreign');
        });

        Schema::drop(Table::SUBSCRIPTION);
    }
}