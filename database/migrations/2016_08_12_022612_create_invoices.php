<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Invoice\Entity;
use RZP\Models\Order;
use RZP\Constants\Table;
use RZP\Models\Customer;
use RZP\Models\Merchant;

class CreateInvoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::INVOICE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::ORDER_ID, Entity::ID_LENGTH);

            $table->char(Entity::CUSTOMER_ID, Entity::ID_LENGTH);

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->integer(Entity::DUE_BY);

            $table->integer(Entity::SCHEDULED_AT);

            $table->string(Entity::STATUS, 32);

            $table->string(Entity::EMAIL_STATUS, 32)
                  ->nullable();

            $table->string(Entity::SMS_STATUS, 32)
                  ->nullable();

            $table->string(Entity::CUSTOMER_ADDRESS)
                  ->nullable();

            $table->string(Entity::CUSTOMER_NAME)
                  ->nullable();

            $table->string(Entity::CUSTOMER_EMAIL)
                  ->nullable();

            $table->string(Entity::CUSTOMER_CONTACT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::STATUS);
            $table->index(Entity::DUE_BY);
            $table->index(Entity::SCHEDULED_AT);
            $table->index(Entity::EMAIL_STATUS);
            $table->index(Entity::SMS_STATUS);

            $table->foreign(Entity::ORDER_ID)
                  ->references(Order\Entity::ID)
                  ->on(Table::ORDER)
                  ->on_delete('restrict');

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
        Schema::table(Table::INVOICE, function($table)
        {
            $table->dropForeign
            (
                Table::INVOICE . '_' . Entity::ORDER_ID . '_foreign'
            );
        });

        Schema::table(Table::INVOICE, function($table)
        {
            $table->dropForeign
            (
                Table::TRANSACTION . '_' . Entity::CUSTOMER_ID . '_foreign'
            );
        });

        Schema::table(Table::INVOICE, function($table)
        {
            $table->dropForeign
            (
                Table::MERCHANT . '_' . Entity::MERCHANT_ID . '_foreign'
            );
        });

        Schema::drop(Table::INVOICE);
    }
}
