<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Gateway\FirstData\Entity as FirstData;
use RZP\Constants\Table;

class CreateFirstDataGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::FIRST_DATA, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(FirstData::ID);

            $table->char(FirstData::PAYMENT_ID, FirstData::ID_LENGTH);

            $table->string(FirstData::ACTION, 10)
                  ->nullable();

            $table->tinyInteger(FirstData::RECEIVED)
                  ->default(0);

            $table->string(FirstData::REFUND_ID, FirstData::ID_LENGTH)
                  ->nullable();

            $table->integer(FirstData::AMOUNT);

            $table->string(FirstData::CURRENCY, 3)
                  ->nullable();

            $table->string(FirstData::STATUS, 20)
                  ->nullable();

            $table->string(FirstData::TRANSACTION_RESULT)
                  ->nullable();

            $table->string(FirstData::GATEWAY_PAYMENT_ID, 20)
                  ->nullable();

            $table->string(FirstData::TDATE, 20)
                  ->nullable();

            $table->string(FirstData::GATEWAY_TRANSACTION_ID, 20)
                  ->nullable();

            $table->string(FirstData::CAPS_PAYMENT_ID, FirstData::ID_LENGTH)
                  ->nullable();

            $table->string(FirstData::ENDPOINT_TRANSACTION_ID, 20)
                  ->nullable();

            $table->string(FirstData::GATEWAY_TERMINAL_ID, 20)
                  ->nullable();

            $table->string(FirstData::AUTH_CODE, 6)
                  ->nullable();

            $table->string(FirstData::APPROVAL_CODE, 100)
                  ->nullable();

            $table->string(FirstData::ERROR_MESSAGE, 255)
                  ->nullable();

            $table->char(FirstData::ARN_NO, 40)
                  ->nullable();

            $table->integer(FirstData::CREATED_AT);

            $table->integer(FirstData::UPDATED_AT);

            $table->foreign(FirstData::PAYMENT_ID)
                  ->references(FirstData::ID)
                  ->on(Table::PAYMENT)
                  ->on_delete('restrict');

            $table->index(FirstData::STATUS);

            $table->index(FirstData::PAYMENT_ID);

            $table->index(FirstData::RECEIVED);

            $table->index(FirstData::CREATED_AT);

            $table->index(FirstData::REFUND_ID);

            $table->index(FirstData::TDATE);

            $table->index(FirstData::GATEWAY_PAYMENT_ID);

            $table->index(FirstData::GATEWAY_TRANSACTION_ID);

            $table->index(FirstData::ACTION);

            $table->index(FirstData::CAPS_PAYMENT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::FIRST_DATA, function($table)
        {
            $table->dropForeign(Table::FIRST_DATA . '_' . FirstData::PAYMENT_ID . '_foreign');
        });

        Schema::drop(Table::FIRST_DATA);
    }
}
