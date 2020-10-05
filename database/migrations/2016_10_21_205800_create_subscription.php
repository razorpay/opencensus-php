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
use RZP\Models\Schedule;
use RZP\Models\Invoice;

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
            $table->char(Entity::SCHEDULE_ID, Entity::ID_LENGTH);
            $table->char(Entity::CUSTOMER_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->tinyInteger(Entity::GLOBAL_CUSTOMER)
                  ->default(1);
            $table->char(Entity::CUSTOMER_EMAIL, 100)
                  ->nullable()
                  ->default(null);
            $table->char(Entity::TOKEN_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::CURRENT_PAYMENT_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::CURRENT_INVOICE_ID, 18)
                  ->nullable();

            $table->integer(Entity::CURRENT_INVOICE_AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->string(Entity::STATUS, 16)
                  ->default(Status::CREATED);

            $table->string(Entity::ERROR_STATUS, 16)
                  ->nullable();

            $table->integer(Entity::QUANTITY)
                  ->default(1);

            $table->integer(Entity::TOTAL_COUNT);

            $table->integer(Entity::PAID_COUNT)
                  ->default(0);

            $table->integer(Entity::ISSUED_INVOICES_COUNT)
                  ->default(0);

            $table->integer(Entity::AUTH_ATTEMPTS)
                  ->default(0);

            $table->tinyInteger(Entity::CUSTOMER_NOTIFY)
                  ->default(1);

            $table->tinyInteger(Entity::TYPE)
                  ->unsigned()
                  ->default(0);

            $table->string(Entity::SOURCE, 32)
                  ->nullable();

            $table->text(Entity::NOTES);

            $table->integer(Entity::CANCEL_AT)
                  ->nullable();

            $table->integer(Entity::CURRENT_START)
                  ->nullable();
            $table->integer(Entity::CURRENT_END)
                  ->nullable();

            $table->integer(Entity::START_AT)
                  ->nullable();
            $table->integer(Entity::END_AT)
                  ->nullable();

            $table->integer(Entity::CHARGE_AT)
                  ->nullable();

            $table->integer(Entity::ACTIVATED_AT)
                  ->nullable();
            $table->integer(Entity::CANCELLED_AT)
                  ->nullable();
            $table->integer(Entity::AUTHENTICATED_AT)
                  ->nullable();

            $table->integer(Entity::ENDED_AT)
                  ->nullable();

            $table->integer(Entity::FAILED_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->string(Entity::CUSTOMER_NAME, 255)
                  ->nullable();

            $table->string(Entity::CUSTOMER_CONTACT, 255)
                  ->nullable();

            $table->index(Entity::CANCEL_AT);
            $table->index(Entity::START_AT);
            $table->index(Entity::END_AT);
            $table->index(Entity::CHARGE_AT);
            $table->index(Entity::ENDED_AT);
            $table->index(Entity::FAILED_AT);
            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index(Entity::CUSTOMER_EMAIL);
            $table->index(Entity::CUSTOMER_CONTACT);
            $table->index([Entity::MERCHANT_ID, Entity::CREATED_AT]);
            $table->index([Entity::STATUS, Entity::START_AT]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::SUBSCRIPTION);
    }
}
