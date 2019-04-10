<?php

use RZP\Constants\Table;
use RZP\Gateway\Isg\Entity as ISG;
use Illuminate\Support\Facades\Schema;
use RZP\Models\Payment\Entity as Payment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Payment\Refund\Entity as Refund;

class CreateIsgGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ISG, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(ISG::ID);

            $table->char(ISG::PAYMENT_ID, Payment::ID_LENGTH);

            $table->char(ISG::REFUND_ID, Refund::ID_LENGTH)
                  ->nullable();

            $table->char(ISG::ACTION, 10)
                  ->nullable();

            $table->integer(ISG::RECEIVED)
                  ->default(0);

            $table->string(ISG::MERCHANT_REFERENCE)
                  ->nullable();

            $table->string(ISG::SECONDARY_ID)
                  ->nullable();

            $table->string(ISG::BANK_REFERENCE_NUMBER, 64)
                  ->nullable();

            $table->char(ISG::MERCHANT_PAN, 16);

            $table->dateTime(ISG::TRANSACTION_DATE_TIME)
                  ->nullable();

            $table->integer(ISG::AMOUNT)
                  ->nullable();

            $table->char(ISG::AUTH_CODE, 6)
                  ->nullable();

            $table->char(ISG::RRN, 12)
                  ->nullable();

            $table->integer(ISG::TIP_AMOUNT)
                  ->nullable();

            $table->char(ISG::STATUS_CODE, 10)
                  ->nullable();

            $table->char(ISG::STATUS_DESC, 30)
                  ->nullable();

            $table->integer(ISG::CREATED_AT);

            $table->integer(ISG::UPDATED_AT);

            $table->index(ISG::ACTION);

            $table->index(ISG::PAYMENT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::ISG);
    }
}
