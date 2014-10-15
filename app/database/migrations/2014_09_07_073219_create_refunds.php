<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Payment\Refund\Entity as Refund;
use Models\Payment;
use Models\Merchant;
use Models\Transaction;

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

            $table->char(Refund::PAYMENT_ID, Refund::ID_LENGTH);

            $table->char(Refund::MERCHANT_ID, Refund::ID_LENGTH);

            $table->integer(Refund::AMOUNT);

            $table->char(Refund::CURRENCY, Payment\Entity::CURRENCY_LENGTH);

            $table->char(Refund::TRANSACTION_ID, Refund::ID_LENGTH)
                  ->unique()
                  ->nullable();

            $table->integer(Refund::CREATED_AT);
            $table->integer(Refund::UPDATED_AT);

            $table->foreign(Refund::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Refund::PAYMENT_ID)
                  ->references(Payment\Entity::ID)
                  ->on(Table::PAYMENT)
                  ->on_delete('restrict');

            $table->foreign(Refund::TRANSACTION_ID)
                  ->references(Transaction\Entity::ID)
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
