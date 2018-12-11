<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Gateway\CardlessEmi\Entity as CardlessEmi;

class CreateCardlessEmi extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CARDLESS_EMI, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(CardlessEmi::ID);

            $table->string(CardlessEmi::ACTION, 10)
                  ->nullable();

            $table->char(CardlessEmi::PAYMENT_ID, CardlessEmi::ID_LENGTH);

            $table->string(CardlessEmi::REFUND_ID, CardlessEmi::ID_LENGTH)
                  ->nullable();

            $table->string(CardlessEmi::GATEWAY);

            $table->string(CardlessEmi::PROVIDER);

            $table->string(CardlessEmi::AMOUNT);

            $table->char(CardlessEmi::CURRENCY, 3)
                  ->nullable();

            $table->char(CardlessEmi::GATEWAY_REFERENCE_ID)
                  ->nullable();

            $table->integer(CardlessEmi::GATEWAY_PLAN_ID)
                  ->nullable();

            $table->string(CardlessEmi::STATUS, 50)
                  ->nullable();

            $table->integer(CardlessEmi::RECEIVED)
                  ->default(0);

            $table->string(CardlessEmi::ERROR_CODE)
                  ->nullable();

            $table->string(CardlessEmi::ERROR_DESCRIPTION)
                  ->nullable();

            $table->string(CardlessEmi::CONTACT, 20);

            $table->string(CardlessEmi::EMAIL)
                  ->nullable();

            $table->integer(CardlessEmi::CREATED_AT);
            $table->integer(CardlessEmi::UPDATED_AT);

            $table->index(CardlessEmi::PAYMENT_ID);

            $table->index(CardlessEmi::GATEWAY);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::CARDLESS_EMI);
    }
}
