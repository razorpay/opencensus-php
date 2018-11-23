<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\Customer\Transaction\Entity;

class CreateCustomerTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CUSTOMER_TRANSACTION, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::ENTITY_ID, Entity::ID_LENGTH);

            $table->string(Entity::ENTITY_TYPE, 50);

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::CUSTOMER_ID, Entity::ID_LENGTH);

            $table->string(Entity::STATUS, 50);

            $table->string(Entity::TYPE, 30);

            $table->bigInteger(Entity::AMOUNT)
                  ->unsigned();

            $table->char(Entity::CURRENCY, 10);

            $table->bigInteger(Entity::DEBIT)
                  ->unsigned();

            $table->bigInteger(Entity::CREDIT)
                  ->unsigned();

            $table->bigInteger(Entity::BALANCE)
                  ->nullable();

            $table->string(Entity::DESCRIPTION, 255)
                  ->nullable();

            $table->integer(Entity::RECONCILED_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::UPDATED_AT);

            $table->index(Entity::ENTITY_ID);

            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Entity::CUSTOMER_ID)
                  ->references(Customer\Entity::ID)
                  ->on(Table::CUSTOMER)
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
        Schema::table(Table::CUSTOMER_TRANSACTION, function($table)
        {
            $table->dropForeign(
                Table::CUSTOMER_TRANSACTION . '_' . Entity::MERCHANT_ID . '_foreign');
        });

        Schema::table(Table::CUSTOMER_TRANSACTION, function($table)
        {
            $table->dropForeign(
                Table::CUSTOMER_TRANSACTION . '_' . Entity::CUSTOMER_ID . '_foreign');
        });

        Schema::drop(Table::CUSTOMER_TRANSACTION);
    }
}
