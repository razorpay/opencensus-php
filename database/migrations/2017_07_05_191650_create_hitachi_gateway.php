<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Payment\Entity as Payment;
use RZP\Gateway\Hitachi\Entity as Hitachi;
use RZP\Models\Payment\Refund\Entity as Refund;

class CreateHitachiGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::HITACHI, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Hitachi::ID);

            $table->char(Hitachi::PAYMENT_ID, Payment::ID_LENGTH);

            $table->char(Hitachi::REFUND_ID, Refund::ID_LENGTH)
                  ->nullable();

            $table->char(Hitachi::ACQUIRER, 10)
                  ->nullable();

            $table->char(Hitachi::ACTION, 10)
                  ->nullable();

            $table->integer(Hitachi::RECEIVED)
                  ->default(0);

            $table->integer(Hitachi::AMOUNT);

            $table->char(Hitachi::CURRENCY, 3)
                  ->nullable();

            $table->char(Hitachi::REQUEST_ID, 50)
                  ->nullable();

            $table->string(Hitachi::RESPONSE_CODE)
                  ->nullable();

            $table->char(Hitachi::AUTH_STATUS, 1)
                  ->nullable();

            $table->char(Hitachi::RRN, 12)
                  ->nullable();

            $table->string(Hitachi::STATUS)
                  ->nullable();

            $table->string(Hitachi::MASKED_CARD_NUMBER)
                  ->nullable();

            $table->string(Hitachi::CARD_NETWORK)
                  ->nullable();

            $table->string(Hitachi::MERCHANT_REFERENCE)
                  ->nullable();

            $table->char(Hitachi::AUTH_ID, 6)
                  ->nullable();

            $table->integer(Hitachi::CREATED_AT);

            $table->integer(Hitachi::UPDATED_AT);

            $table->string(Hitachi::AUTHENTICATION_GATEWAY)
                  ->nullable();

            $table->foreign(Hitachi::PAYMENT_ID)
                  ->references(Payment::ID)
                  ->on(Table::PAYMENT)
                  ->onDelete('restrict');

            $table->index(Hitachi::RECEIVED);

            $table->index(Hitachi::CREATED_AT);

            $table->index(Hitachi::REFUND_ID);

            $table->index(Hitachi::REQUEST_ID);

            $table->index(Hitachi::RRN);

            $table->index(Hitachi::ACTION);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::HITACHI, function(Blueprint $table)
        {
            $table->dropForeign(Table::HITACHI . '_' . Hitachi::PAYMENT_ID . '_foreign');
        });

        Schema::drop(Table::HITACHI);
    }
}
