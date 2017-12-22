<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Payout\Entity as Payout;
use RZP\Models\FundTransfer\Batch as BatchFundTransfer;
use RZP\Models\Transaction;

class CreatePayoutsTable extends Migration
{
    /**
    * Run the migrations.
    *
    * @return void
    */
    public function up()
    {
        Schema::create(Table::PAYOUT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Payout::ID, Payout::ID_LENGTH)
                  ->primary();

            $table->char(Payout::MERCHANT_ID, Payout::ID_LENGTH);

            $table->char(Payout::CUSTOMER_ID, Customer\Entity::ID_LENGTH);

            $table->string(Payout::METHOD);

            $table->char(Payout::DESTINATION_ID, Payout::ID_LENGTH);

            $table->char(Payout::DESTINATION_TYPE, 20);

            $table->char(Payout::PURPOSE, 30);

            $table->integer(Payout::AMOUNT)
                  ->unsigned();

            $table->char(Payout::CURRENCY);

            $table->char(Payout::PAYMENT_ID, Payment\Entity::ID_LENGTH)
                  ->nullable()
                  ->default(null);

            $table->string(Payout::NOTES)
                  ->nullable();

            $table->integer(Payout::FEES)
                  ->unsigned()
                  ->default(0);

            $table->integer(Payout::TAX)
                  ->unsigned()
                  ->default(0);

            $table->string(Payout::STATUS);

            $table->char(Payout::TRANSACTION_ID, Payout::ID_LENGTH)
                  ->nullable()
                  ->unique();

            $table->char(Payout::BATCH_FUND_TRANSFER_ID, Payout::ID_LENGTH)
                  ->nullable();

            $table->string(Payout::CHANNEL, 8);

            $table->string(Payout::UTR)
                  ->nullable()
                  ->unique();

            $table->string(Payout::FAILURE_REASON)
                  ->nullable();

            $table->string(Payout::RETURN_UTR)
                  ->nullable()
                  ->unique();

            $table->string(Payout::REMARKS)
                  ->nullable();

            $table->integer(Payout::PROCESSED_AT)
                  ->nullable();

            $table->integer(Payout::SETTLED_ON)
                  ->nullable();

            $table->integer(Payout::CREATED_AT);

            $table->integer(Payout::UPDATED_AT);

            $table->index(Payout::CREATED_AT);

            $table->index(Payout::METHOD);

            $table->index(Payout::STATUS);

            $table->index([Payout::MERCHANT_ID, Payout::CREATED_AT]);

            $table->foreign(Payout::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Payout::CUSTOMER_ID)
                  ->references(Customer\Entity::ID)
                  ->on(Table::CUSTOMER)
                  ->on_delete('restrict');

            $table->foreign(Payout::PAYMENT_ID)
                  ->references(Payment\Entity::ID)
                  ->on(Table::PAYMENT)
                  ->on_delete('restrict');

            $table->foreign(Payout::TRANSACTION_ID)
                  ->references(Transaction\Entity::ID)
                  ->on(Table::TRANSACTION)
                  ->on_delete('restrict');

            $table->foreign(Payout::BATCH_FUND_TRANSFER_ID)
                  ->references(BatchFundTransfer\Entity::ID)
                  ->on(Table::BATCH_FUND_TRANSFER)
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
        Schema::table(Table::PAYOUT, function($table)
        {
            $table->dropForeign(Table::PAYOUT . '_' . Payout::CUSTOMER_ID . '_foreign');

            $table->dropForeign(Table::PAYOUT . '_' . Payout::MERCHANT_ID . '_foreign');

            $table->dropForeign(Table::PAYOUT . '_' . Payout::PAYMENT_ID . '_foreign');

            $table->dropForeign(Table::PAYOUT . '_' . Payout::TRANSACTION_ID . '_foreign');

            $table->dropForeign(Table::PAYOUT . '_' . Payout::BATCH_FUND_TRANSFER_ID . '_foreign');
        });

        Schema::drop(Table::PAYOUT);

    }
}
