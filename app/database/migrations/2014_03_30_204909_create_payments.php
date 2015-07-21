<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Payment\Entity as Payment;
use Models\Merchant;
use Models\Card;
use Models\Terminal;
use Models\Transaction;

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

            $table->string(Payment::WALLET, 8)
                  ->nullable();

            $table->string(Payment::ERROR_CODE, 100)
                  ->nullable();

            $table->string(Payment::ERROR_DESCRIPTION, 255)
                  ->nullable();

            $table->string(Payment::EMAIL, 255)
                  ->nullable();

            $table->string(Payment::CONTACT, 20);

            $table->string(Payment::NOTES);

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

            $table->boolean(Payment::VERIFIED)
                  ->nullable();

            $table->string(Payment::CALLBACK_URL)
                  ->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer(Payment::CREATED_AT);
            $table->integer(Payment::UPDATED_AT);

            $table->index(Payment::STATUS);
            $table->index(Payment::CREATED_AT);
            $table->index(Payment::AUTO_CAPTURED);
            $table->index(Payment::VERIFIED);

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
