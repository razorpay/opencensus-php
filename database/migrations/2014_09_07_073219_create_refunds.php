<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Payment\Refund\Entity as Refund;
use RZP\Models\Transaction\Entity as Transaction;
use RZP\Models\BankAccount\Entity as BankAccount;

class CreateRefunds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::REFUND, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Refund::ID, Refund::ID_LENGTH)
                  ->primary();

            $table->char(Refund::PAYMENT_ID, Payment::ID_LENGTH);

            $table->char(Refund::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->integer(Refund::AMOUNT)
                  ->unsigned();

            $table->char(Refund::CURRENCY, Payment::CURRENCY_LENGTH);

            $table->integer(Refund::BASE_AMOUNT)
                  ->unsigned();

            $table->string(Refund::STATUS)
                  ->nullable();

            $table->string(Payment::ERROR_CODE, 128)
                  ->nullable();

            $table->string(Payment::INTERNAL_ERROR_CODE)
                  ->nullable();

            $table->string(Payment::ERROR_DESCRIPTION, 255)
                  ->nullable();

            $table->string(Refund::GATEWAY)
                  ->nullable();

            $table->tinyInteger(Refund::GATEWAY_REFUNDED)
                  ->nullable();

            $table->text(Refund::NOTES);

            $table->string(Refund::RECEIPT)
                  ->nullable();

            $table->char(Refund::TRANSACTION_ID, Transaction::ID_LENGTH)
                  ->unique()
                  ->nullable();

            $table->char(Refund::BATCH_FUND_TRANSFER_ID, Refund::ID_LENGTH)
                  ->nullable();

            $table->tinyInteger(Refund::ATTEMPTS)
                  ->nullable();

            $table->integer(Refund::LAST_ATTEMPTED_AT)
                  ->nullable();

            $table->string(Refund::REFERENCE1)
                  ->nullable();

            $table->string(Refund::REFERENCE2)
                  ->nullable();

            $table->tinyInteger(Refund::REFERENCE3)
                  ->nullable();

            $table->tinyInteger(Refund::REFERENCE4)
                  ->nullable();

            $table->integer(Refund::REFERENCE5)
                  ->nullable();

            $table->integer(Refund::REFERENCE6)
                  ->nullable();

            $table->string(Refund::REFERENCE7)
                  ->nullable();

            $table->string(Refund::BANK_ACCOUNT_ID)
                  ->nullable();

            $table->bigInteger(Refund::REFERENCE9)
                  ->unsigned()
                  ->nullable();

            $table->integer(Refund::CREATED_AT);
            $table->integer(Refund::UPDATED_AT);

            $table->index(Refund::AMOUNT);
            $table->index(Refund::STATUS);
            $table->index(Refund::GATEWAY_REFUNDED);
            $table->index(Refund::ATTEMPTS);
            $table->index(Refund::CREATED_AT);
            $table->index(Refund::LAST_ATTEMPTED_AT);
            $table->index(Refund::REFERENCE1);
            $table->index(Refund::UPDATED_AT);
            $table->index([Refund::MERCHANT_ID, Refund::CREATED_AT]);

            $table->unique([Refund::MERCHANT_ID, Refund::RECEIPT]);

            $table->foreign(Refund::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Refund::PAYMENT_ID)
                  ->references(Payment::ID)
                  ->on(Table::PAYMENT)
                  ->on_delete('restrict');

            $table->foreign(Refund::TRANSACTION_ID)
                  ->references(Transaction::ID)
                  ->on(Table::TRANSACTION)
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
        Schema::table(Table::REFUND, function($table)
        {
            $table->dropForeign(Table::REFUND.'_'.Refund::TRANSACTION_ID.'_foreign');

            $table->dropForeign(Table::REFUND.'_'.Refund::PAYMENT_ID.'_foreign');

            $table->dropForeign(Table::REFUND.'_'.Refund::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::REFUND);
    }
}
