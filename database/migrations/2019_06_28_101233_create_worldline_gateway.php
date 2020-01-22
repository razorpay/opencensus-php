<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use RZP\Models\Payment\Entity as Payment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Gateway\Worldline\Entity as Worldline;
use RZP\Models\Payment\Refund\Entity as Refund;

class CreateWorldlineGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::WORLDLINE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Worldline::ID);

            $table->char(Worldline::PAYMENT_ID, Payment::ID_LENGTH);

            $table->char(Worldline::REFUND_ID, Refund::ID_LENGTH)
                  ->nullable();

            $table->char(Worldline::ACTION, 10)
                  ->nullable();

            $table->integer(Worldline::RECEIVED)
                  ->default(0);

            $table->string(Worldline::MID)
                  ->nullable();

            $table->string(Worldline::TXN_CURRENCY)
                  ->nullable();

            $table->string(Worldline::TXN_AMOUNT)
                  ->nullable();

            $table->string(Worldline::AUTH_CODE)
                  ->nullable();

            $table->string(Worldline::REF_NO)
                  ->nullable();

            $table->string(Worldline::GATEWAY_UTR, 255)
                  ->nullable();

            $table->string(Worldline::TRANSACTION_TYPE)
                  ->nullable();

            $table->string(Worldline::BANK_CODE)
                  ->nullable();

            $table->string(Worldline::AGGREGATOR_ID)
                  ->nullable();

            $table->string(Worldline::CUSTOMER_VPA)
                  ->nullable();

            $table->string(Worldline::PRIMARY_ID)
                  ->nullable();

            $table->string(Worldline::SECONDARY_ID)
                  ->nullable();

            $table->string(Worldline::CREATED_AT);

            $table->string(Worldline::UPDATED_AT);

            $table->index(Worldline::ACTION);

            $table->index(Worldline::PAYMENT_ID);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::WORLDLINE);
    }
}
