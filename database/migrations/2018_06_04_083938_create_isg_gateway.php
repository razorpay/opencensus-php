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

            $table->char(ISG::BANK_REFERENCE_NUMBER, 16)
                ->nullable();

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

            $table->char(ISG::STATUS_CODE, 2)
                ->nullable();

            $table->char(ISG::STATUS_DESC, 30)
                ->nullable();

            $table->string(ISG::CREATED_AT);

            $table->string(ISG::UPDATED_AT);

            $table->index(ISG::ACTION);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('isg');
    }
}
