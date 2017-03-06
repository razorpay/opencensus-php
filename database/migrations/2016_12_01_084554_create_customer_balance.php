<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Customer\Balance\Entity;
use RZP\Models\Customer;
use RZP\Models\Merchant;

class CreateCustomerBalance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CUSTOMER_BALANCE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::CUSTOMER_ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->integer(Entity::BALANCE)
                  ->unsigned();

            $table->integer(Entity::DAILY_USAGE)
                  ->unsigned();

            $table->integer(Entity::WEEKLY_USAGE)
                  ->unsigned();

            $table->integer(Entity::MONTHLY_USAGE)
                  ->unsigned();

            $table->integer(Entity::MAX_BALANCE)
                  ->unsigned()
                  ->default(null)
                  ->nullable();

            $table->integer(Entity::LAST_LOADED_AT)
                  ->default(null)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::UPDATED_AT);

            $table->foreign(Entity::CUSTOMER_ID)
                  ->references(Customer\Entity::ID)
                  ->on(Table::CUSTOMER)
                  ->on_delete('restrict');

            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
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
        Schema::table(Table::CUSTOMER_BALANCE, function($table)
        {
            $table->dropForeign(
                Table::CUSTOMER_BALANCE . '_' . Entity::CUSTOMER_ID . '_foreign');

            $table->dropForeign(
                Table::CUSTOMER_BALANCE . '_' . Entity::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::CUSTOMER_BALANCE);
    }
}
