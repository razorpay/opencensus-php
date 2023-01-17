<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Order;
use RZP\Models\Address;
use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Invoice\Entity;

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

            $table->string(Entity::RECEIPT, 40)
                  ->nullable();

            $table->char(Entity::ORDER_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::CUSTOMER_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::ENTITY_TYPE, 255)
                  ->nullable();

            $table->char(Entity::ENTITY_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::REF_NUM, Entity::ID_LENGTH)
                ->nullable();

            $table->char(Entity::CUSTOMER_BILLING_ADDR_ID, Address\Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::CUSTOMER_SHIPPING_ADDR_ID, Address\Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::SUBSCRIPTION_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::BATCH_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::IDEMPOTENCY_KEY,255)
                  ->unique()
                  ->nullable();

            $table->integer(Entity::DATE)
                  ->nullable();

            $table->integer(Entity::DUE_BY);

            $table->integer(Entity::SCHEDULED_AT);

            $table->integer(Entity::ISSUED_AT)
                  ->nullable();

            $table->integer(Entity::PAID_AT)
                  ->nullable();

            $table->integer(Entity::CANCELLED_AT)
                  ->nullable();

            $table->integer(Entity::EXPIRED_AT)
                  ->nullable();

            $table->integer(Entity::EXPIRE_BY)
                  ->nullable();

            $table->bigInteger(Entity::BIG_EXPIRE_BY)
                  ->unsigned()
                  ->nullable();

            $table->string(Entity::STATUS, 32);

            $table->string(Entity::SUBSCRIPTION_STATUS, 32)
                  ->nullable();

            $table->string(Entity::EMAIL_STATUS, 32)
                  ->nullable();

            $table->string(Entity::SMS_STATUS, 32)
                  ->nullable();

            $table->tinyInteger(Entity::PARTIAL_PAYMENT)
                  ->default(false);

            $table->bigInteger(Entity::FIRST_PAYMENT_MIN_AMOUNT)
                   ->unsigned()
                   ->nullable();

            $table->integer(Entity::GROSS_AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->integer(Entity::TAX_AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->integer(Entity::OFFER_AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->bigInteger(Entity::AMOUNT)
                  ->nullable();

            $table->string(Entity::CURRENCY, 3);

            $table->string(Entity::CUSTOMER_NAME)
                  ->nullable();

            $table->string(Entity::CUSTOMER_EMAIL)
                  ->nullable();

            $table->string(Entity::CUSTOMER_CONTACT)
                  ->nullable();

            $table->string(Entity::CUSTOMER_GSTIN, 20)
                  ->nullable();

            $table->string(Entity::MERCHANT_GSTIN, 20)
                  ->nullable();

            $table->string(Entity::MERCHANT_LABEL, 255)
                  ->nullable();

            $table->char(Entity::SUPPLY_STATE_CODE, 4)
                  ->nullable();

            $table->text(Entity::DESCRIPTION)
                  ->nullable();

            $table->text(Entity::TERMS)
                  ->nullable();

            $table->text(Entity::NOTES);

            $table->text(Entity::COMMENT)
                  ->nullable();

            $table->string(Entity::SHORT_URL, 255)
                  ->nullable();

            $table->tinyInteger(Entity::VIEW_LESS)
                  ->default(1);

            $table->string(Entity::TYPE, 16)
                  ->nullable();

            $table->string(Entity::SOURCE, 32)
                  ->nullable();

            $table->integer(Entity::BILLING_START)
                  ->nullable();

            $table->integer(Entity::BILLING_END)
                  ->nullable();

            $table->char(Entity::USER_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->tinyInteger(Entity::GROUP_TAXES_DISCOUNTS)
                  ->default(0);

            $table->text(Entity::CALLBACK_URL)
                  ->nullable();

            $table->string(Entity::CALLBACK_METHOD, 16)
                  ->nullable();

            $table->string(Entity::INTERNAL_REF, 64)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);
            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index(Entity::DELETED_AT);
            $table->index(Entity::STATUS);
            $table->index(Entity::SUBSCRIPTION_STATUS);
            $table->index(Entity::DUE_BY);
            $table->index(Entity::SCHEDULED_AT);
            $table->index(Entity::EMAIL_STATUS);
            $table->index(Entity::SMS_STATUS);
            $table->index(Entity::USER_ID);
            $table->index(Entity::EXPIRE_BY);
            $table->index(Entity::AMOUNT);
            $table->index(Entity::CUSTOMER_NAME);
            $table->index(Entity::CUSTOMER_CONTACT);
            $table->index(Entity::CUSTOMER_EMAIL);
            $table->index(Entity::TYPE);
            $table->index(Entity::SOURCE);
            $table->index([Entity::MERCHANT_ID, Entity::CREATED_AT]);
            $table->index([Entity::MERCHANT_ID, Entity::RECEIPT]);
            $table->index([Entity::INTERNAL_REF, Entity::MERCHANT_ID]);
            $table->index([Entity::STATUS, Entity::EXPIRE_BY, Entity::DELETED_AT]);
            $table->index([Entity::USER_ID, Entity::MERCHANT_ID, Entity::TYPE, Entity::ENTITY_TYPE]);

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

            $table->foreign(Entity::CUSTOMER_BILLING_ADDR_ID)
                  ->references(Address\Entity::ID)
                  ->on(Table::ADDRESS)
                  ->on_delete('restrict');

            $table->foreign(Entity::CUSTOMER_SHIPPING_ADDR_ID)
                  ->references(Address\Entity::ID)
                  ->on(Table::ADDRESS)
                  ->on_delete('restrict');
        });

        // This should be here and not in payments table because
        // invoice table is created after payments.
        Schema::table(Table::PAYMENT, function($table)
        {
            $table->foreign(Payment\Entity::INVOICE_ID)
                ->references(Entity::ID)
                ->on(Table::INVOICE)
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
                Table::INVOICE . '_' . Entity::CUSTOMER_ID . '_foreign'
            );

            $table->dropForeign
            (
                Table::INVOICE . '_' . Entity::MERCHANT_ID . '_foreign'
            );

            $table->dropForeign
            (
                Table::INVOICE . '_' . Entity::CUSTOMER_BILLING_ADDR_ID . '_foreign'
            );

            $table->dropForeign
            (
                Table::INVOICE . '_' . Entity::CUSTOMER_SHIPPING_ADDR_ID . '_foreign'
            );
        });

        Schema::table(Table::PAYMENT, function($table)
        {
            $table->dropForeign(
                Table::PAYMENT . '_' . Payment\Entity::INVOICE_ID . '_foreign');
        });

        Schema::drop(Table::INVOICE);
    }
}
