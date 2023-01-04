<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Payment\Entity as Payment;

class CreatePaymentsNew extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments_new', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Payment::ID, Payment::ID_LENGTH)
                ->primary();

            $table->char(Payment::MERCHANT_ID, Payment::ID_LENGTH);

            $table->bigInteger(Payment::AMOUNT)
                ->unsigned();

            $table->char(Payment::CURRENCY, Payment::CURRENCY_LENGTH);

            $table->bigInteger(Payment::BASE_AMOUNT)
                ->unsigned();

            $table->string(Payment::METHOD);

            $table->string(Payment::STATUS);

            $table->string(Payment::TWO_FACTOR_AUTH, 20)
                ->nullable();

            $table->char(Payment::ORDER_ID, Payment::ID_LENGTH)
                ->nullable();

            $table->char(Payment::INVOICE_ID, Payment::ID_LENGTH)
                ->nullable();

            $table->char(Payment::TRANSFER_ID, Payment::ID_LENGTH)
                ->nullable();

            $table->char(Payment::PAYMENT_LINK_ID, Payment::ID_LENGTH)
                ->nullable();

            $table->char(Payment::RECEIVER_ID, Payment::ID_LENGTH)
                ->nullable();

            $table->string(Payment::RECEIVER_TYPE)
                ->nullable();

            $table->tinyInteger(Payment::INTERNATIONAL)
                ->nullable();

            $table->bigInteger(Payment::AMOUNT_AUTHORIZED)
                ->unsigned()
                ->default(0);

            $table->bigInteger(Payment::AMOUNT_REFUNDED)
                ->unsigned()
                ->default(0);

            $table->bigInteger(Payment::BASE_AMOUNT_REFUNDED)
                ->unsigned()
                ->default(0);

            $table->bigInteger(Payment::AMOUNT_TRANSFERRED)
                ->unsigned()
                ->default(0);

            $table->bigInteger(Payment::AMOUNT_PAIDOUT)
                ->unsigned()
                ->default(0);

            $table->string(Payment::REFUND_STATUS)
                ->nullable();

            $table->string(Payment::DESCRIPTION)
                ->nullable();

            $table->char(Payment::CARD_ID, Payment::ID_LENGTH)
                ->nullable();

            $table->char(Payment::SUBSCRIPTION_ID, Payment::ID_LENGTH)
                ->nullable();

            $table->char(Payment::BANK, 6)
                ->nullable();

            $table->string(Payment::WALLET, 15)
                ->nullable();

            $table->string(Payment::VPA, 100)
                ->nullable();

            $table->tinyInteger(Payment::ON_HOLD)
                ->default(0);

            $table->integer(Payment::ON_HOLD_UNTIL)
                ->nullable()
                ->default(null);

            $table->char(Payment::EMI_PLAN_ID, 14)
                ->nullable();

            $table->string(Payment::EMI_SUBVENTION, 10)
                ->nullable();

            $table->string(Payment::ERROR_CODE, 100)
                ->nullable();

            $table->string(Payment::INTERNAL_ERROR_CODE)
                ->nullable();

            $table->string(Payment::ERROR_DESCRIPTION, 255)
                ->nullable();

            $table->string(Payment::CANCELLATION_REASON, 255)
                ->nullable();

            $table->string(Payment::CUSTOMER_ID, 14)
                ->nullable();

            $table->string(Payment::GLOBAL_CUSTOMER_ID, 14)
                ->nullable();

            $table->string(Payment::APP_TOKEN, 14)
                ->nullable();

            $table->string(Payment::TOKEN_ID, 14)
                ->nullable();

            $table->string(Payment::GLOBAL_TOKEN_ID, 14)
                ->nullable();

            $table->string(Payment::EMAIL, 255)
                ->nullable();

            $table->string(Payment::CONTACT, 20)
                ->nullable();

            $table->text(Payment::NOTES);

            $table->char(Payment::TRANSACTION_ID, Payment::ID_LENGTH)
                ->nullable();

            $table->integer(Payment::AUTHORIZED_AT)
                ->nullable();

            $table->integer(Payment::AUTHENTICATED_AT)
                ->nullable();

            $table->tinyInteger(Payment::AUTO_CAPTURED)
                ->default(0);

            $table->integer(Payment::CAPTURED_AT)
                ->nullable();

            $table->string(Payment::GATEWAY)
                ->nullable();

            $table->char(Payment::TERMINAL_ID, Payment::ID_LENGTH)
                ->nullable();

            $table->string(Payment::AUTHENTICATION_GATEWAY)
                ->nullable();

            $table->string(Payment::BATCH_ID)
                ->nullable();

            $table->string(Payment::REFERENCE1)
                ->nullable();

            $table->string(Payment::REFERENCE2)
                ->nullable();

            $table->tinyInteger(Payment::CAPTURE)
                ->default(0)
                ->nullable();

            $table->tinyInteger(Payment::CPS_ROUTE)
                ->default(Payment::API)
                ->nullable();

            $table->integer(Payment::CONVENIENCE_FEE_GST)
                ->nullable();

            $table->integer(Payment::IS_PUSHED_TO_KAFKA)
                ->nullable();

            $table->bigInteger(Payment::CONVENIENCE_FEE)
                ->unsigned()
                ->nullable();

            $table->tinyInteger(Payment::SIGNED)
                ->default(0);

            $table->tinyInteger(Payment::VERIFIED)
                ->nullable();

            $table->tinyInteger(Payment::GATEWAY_CAPTURED)
                ->nullable();

            $table->tinyInteger(Payment::VERIFY_BUCKET)
                ->nullable();

            $table->integer(Payment::VERIFY_AT)
                ->unsigned()
                ->nullable();

            $table->text(Payment::CALLBACK_URL)
                ->nullable();

            $table->integer(Payment::FEE)
                ->unsigned()
                ->nullable();

            $table->integer(Payment::MDR)
                ->unsigned()
                ->nullable();

            $table->integer(Payment::TAX)
                ->unsigned()
                ->nullable();

            $table->tinyInteger(Payment::OTP_ATTEMPTS)
                ->unsigned()
                ->nullable()
                ->default(null);

            $table->tinyInteger(Payment::OTP_COUNT)
                ->unsigned()
                ->nullable()
                ->default(null);

            $table->tinyInteger(Payment::RECURRING)
                ->default(0);

            $table->tinyInteger(Payment::SAVE)
                ->default(0);

            $table->tinyInteger(Payment::LATE_AUTHORIZED)
                ->nullable();

            $table->tinyInteger(Payment::CONVERT_CURRENCY)
                ->nullable();

            $table->tinyInteger(Payment::DISPUTED)
                ->default(0);

            $table->string(Payment::RECURRING_TYPE)
                ->nullable();

            $table->string(Payment::AUTH_TYPE, 14)
                ->nullable();

            $table->integer(Payment::ACKNOWLEDGED_AT)
                ->unsigned()
                ->nullable();

            $table->integer(Payment::REFUND_AT)
                ->unsigned()
                ->nullable();

            $table->bigInteger(Payment::FEE_BEARER)
                ->default(0) // platform fee bearer
                ->unsigned()
                ->nullable();

            $table->char(Payment::REFERENCE13, Payment::ID_LENGTH)
                ->nullable();

            $table->char(Payment::REFERENCE14, Payment::ID_LENGTH)
                ->nullable();

            $table->string(Payment::SETTLED_BY, 255)
                ->nullable();

            $table->string(Payment::REFERENCE16, 255)
                ->nullable();

            $table->text(Payment::REFERENCE17)
                ->nullable();


            // Adds created_at and updated_at columns to the table
            $table->integer(Payment::CREATED_AT);
            $table->integer(Payment::UPDATED_AT);

            $table->string(Payment::PUBLIC_KEY)
                ->nullable();

            $table->index(Payment::STATUS);
            $table->index(Payment::WALLET);
            $table->index(Payment::TWO_FACTOR_AUTH);
            $table->index(Payment::CREATED_AT);
            $table->index(Payment::AUTO_CAPTURED);
            $table->index(Payment::VERIFIED);

            $table->index(Payment::GATEWAY_CAPTURED);
            $table->index(Payment::VERIFY_BUCKET);
            $table->index(Payment::GATEWAY);
            $table->index(Payment::AUTHORIZED_AT);
            $table->index(Payment::EMAIL);
            $table->index(Payment::BANK);
            $table->index(Payment::AMOUNT);
            $table->index(Payment::METHOD);
            $table->index(Payment::AMOUNT_TRANSFERRED);
            $table->index(Payment::LATE_AUTHORIZED);
            $table->index(Payment::ON_HOLD);
            $table->index(Payment::ON_HOLD_UNTIL);
            $table->index(Payment::AUTH_TYPE);

            $table->index(Payment::DISPUTED);
            $table->index(Payment::RECURRING);
            $table->index(Payment::UPDATED_AT);
            $table->index(Payment::CAPTURED_AT);
            $table->index(Payment::MERCHANT_ID);
            $table->index(Payment::VERIFY_AT);
            $table->index([Payment::MERCHANT_ID, Payment::CREATED_AT], "payments_new_merchant_id_id_index");
            $table->index([Payment::MERCHANT_ID, Payment::STATUS, Payment::CREATED_AT]);
            $table->index([Payment::MERCHANT_ID, Payment::CAPTURED_AT]);
            $table->index([Payment::VERIFY_AT, Payment::GATEWAY]);
            $table->index(Payment::REFUND_AT);

            $table->index(Payment::RECEIVER_ID);
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('payments_new');
    }
}
