<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Merchant;
use RZP\Models\Card;
use RZP\Models\Terminal;
use RZP\Models\Transaction;
use RZP\Models\Order;

class CreatePayments  extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYMENT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Payment::ID, Payment::ID_LENGTH)
                  ->primary();

            $table->char(Payment::MERCHANT_ID, Payment::ID_LENGTH);

            $table->integer(Payment::AMOUNT)
                  ->unsigned();

            $table->string(Payment::METHOD);

            $table->string(Payment::STATUS);

            $table->char(Payment::ORDER_ID, Payment::ID_LENGTH)
                  ->nullable();

            $table->boolean(Payment::INTERNATIONAL)
                  ->nullable();

            $table->integer(Payment::AMOUNT_AUTHORIZED)
                  ->unsigned()
                  ->default(0);

            $table->integer(Payment::AMOUNT_REFUNDED)
                  ->unsigned()
                  ->default(0);

            $table->string(Payment::REFUND_STATUS)
                  ->nullable();

            $table->char(Payment::CURRENCY, Payment::CURRENCY_LENGTH);

            $table->string(Payment::DESCRIPTION)
                  ->nullable();

            $table->char(Payment::CARD_ID, Payment::ID_LENGTH)
                  ->nullable();

            $table->char(Payment::BANK, 6)
                  ->nullable();

            $table->string(Payment::WALLET, 15)
                  ->nullable();

            $table->char(Payment::EMI_PLAN_ID, 14)
                  ->nullable();

            $table->string(Payment::ERROR_CODE, 100)
                  ->nullable();

            $table->string(Payment::INTERNAL_ERROR_CODE)
                  ->nullable();

            $table->string(Payment::ERROR_DESCRIPTION, 255)
                  ->nullable();

            $table->string(Payment::CUSTOMER_ID, 14)
                  ->nullable();

            $table->string(Payment::GLOBAL_CUSTOMER_ID, 14)
                  ->nullable();

            $table->string(Payment::APP_ID, 14)
                  ->nullable();

            $table->string(Payment::APP_TOKEN, 14)
                  ->nullable();

            $table->string(Payment::TOKEN_ID, 14)
                  ->nullable();

            $table->string(Payment::GLOBAL_TOKEN_ID, 14)
                  ->nullable();

            $table->string(Payment::VPA, 50)
                  ->nullable();

            $table->string(Payment::EMAIL, 255)
                  ->nullable();

            $table->string(Payment::CONTACT, 20);

            $table->text(Payment::NOTES);

            $table->char(Payment::TRANSACTION_ID, Payment::ID_LENGTH)
                  ->nullable();

            $table->integer(Payment::AUTHORIZED_AT)
                  ->nullable();

            $table->boolean(Payment::AUTO_CAPTURED)
                  ->default(0);

            $table->integer(Payment::CAPTURED_AT)
                  ->nullable();

            $table->string(Payment::GATEWAY);

            $table->char(Payment::TERMINAL_ID, Payment::ID_LENGTH);

            $table->boolean(Payment::SIGNED)
                  ->default(0);

            $table->tinyInteger(Payment::VERIFIED)
                  ->nullable();

            $table->text(Payment::CALLBACK_URL)
                  ->nullable();

            $table->integer(Payment::FEE)
                  ->unsigned()
                  ->nullable();

            $table->integer(Payment::SERVICE_TAX)
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

            $table->boolean(Payment::SAVE)
                  ->default(0);

            $table->boolean(Payment::LATE_AUTHORIZED)
                  ->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer(Payment::CREATED_AT);
            $table->integer(Payment::UPDATED_AT);

            $table->index(Payment::STATUS);
            $table->index(Payment::CREATED_AT);
            $table->index(Payment::AUTO_CAPTURED);
            $table->index(Payment::VERIFIED);
            $table->index(Payment::AUTHORIZED_AT);
            $table->index(Payment::EMAIL);
            $table->index(Payment::BANK);
            $table->index(Payment::LATE_AUTHORIZED);

            $table->foreign(Payment::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Payment::TERMINAL_ID)
                  ->references(Terminal\Entity::ID)
                  ->on(Table::TERMINAL)
                  ->on_delete('restrict');

            $table->foreign(Payment::TRANSACTION_ID)
                  ->references(Transaction\Entity::ID)
                  ->on(Table::TRANSACTION)
                  ->on_delete('restrict');

            $table->foreign(Payment::CARD_ID)
                  ->references(Card\Entity::ID)
                  ->on(Table::CARD)
                  ->on_delete('restrict');
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::PAYMENT, function($table)
        {
            $table->dropForeign(Table::PAYMENT.'_'.Payment::CARD_ID.'_foreign');

            $table->dropForeign(Table::PAYMENT.'_'.Payment::TRANSACTION_ID.'_foreign');

            $table->dropForeign(Table::PAYMENT.'_'.Payment::TERMINAL_ID.'_foreign');

            $table->dropForeign(Table::PAYMENT.'_'.Payment::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::PAYMENT);
    }
}
