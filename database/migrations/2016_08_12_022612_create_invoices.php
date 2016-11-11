<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Invoice\Entity;
use RZP\Models\Order;
use RZP\Constants\Table;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Address;

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

            $table->char(Entity::MERCHANT_REF_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::ORDER_ID, Entity::ID_LENGTH);

            $table->char(Entity::CUSTOMER_ID, Entity::ID_LENGTH);

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->integer(Entity::DATE)
                  ->nullable();

            $table->integer(Entity::DUE_BY)
              ->nullable();

            $table->integer(Entity::SCHEDULED_AT)
              ->nullable();

            $table->integer(Entity::ISSUED_AT)
                  ->nullable();

            $table->integer(Entity::PAID_AT)
                  ->nullable();

            $table->integer(Entity::EXPIRED_AT)
                  ->nullable();

            $table->string(Entity::STATUS, 32);

            $table->string(Entity::EMAIL_STATUS, 32)
                  ->nullable();

            $table->string(Entity::SMS_STATUS, 32)
                  ->nullable();

            $table->bigInteger(Entity::AMOUNT);

            $table->string(Entity::CURRENCY, 3);

            $table->char(Entity::CUSTOMER_ADDRESS, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::CUSTOMER_NAME)
                  ->nullable();

            $table->string(Entity::CUSTOMER_EMAIL)
                  ->nullable();

            $table->string(Entity::CUSTOMER_CONTACT)
                  ->nullable();

            $table->text(Entity::TERMS)
                  ->nullable();

            $table->text(Entity::NOTES);

            $table->string(Entity::SHORT_URL, 40)
                  ->nullable();

            $table->tinyInteger(Entity::VIEW_LESS)
                  ->default(1);

            $table->string(Entity::TYPE, 16)
                  ->nullable();

            $table->string(Entity::SOURCE, 32)
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

            $table->foreign(Entity::CUSTOMER_ADDRESS)
                  ->references(Address\Entity::ID)
                  ->on(Table::ADDRESS)
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

            $table->dropForeign
            (
                Table::TRANSACTION . '_' . Entity::CUSTOMER_ID . '_foreign'
            );

            $table->dropForeign
            (
                Table::MERCHANT . '_' . Entity::MERCHANT_ID . '_foreign'
            );

            $table->dropForeign
            (
                Table::ADDRESS . '_' . Entity::CUSTOMER_ADDRESS . '_foreign'
            );
        });

        Schema::drop(Table::INVOICE);
    }
}
