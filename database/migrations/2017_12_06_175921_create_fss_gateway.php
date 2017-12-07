<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Gateway\Fss\Entity as Fss;
use RZP\Constants\Table;

class CreateFssGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::FSS, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(FSS::ID);

            $table->char(FSS::PAYMENT_ID, FSS::ID_LENGTH);

            $table->string(FSS::ACTION, 10)
                  ->nullable();

            $table->tinyInteger(FSS::RECEIVED)
                  ->default(0);

            $table->string(FSS::REFUND_ID, FSS::ID_LENGTH)
                  ->nullable();

            $table->integer(FSS::AMOUNT);

            $table->string(FSS::CURRENCY, 3)
                  ->nullable();

            $table->string(FSS::STATUS, 20)
                  ->nullable();

            $table->string(FSS::GATEWAY_PAYMENT_ID, 25)
                  ->nullable();

            $table->string(FSS::GATEWAY_TRANSACTION_ID, 25)
                  ->nullable();

            $table->string(FSS::REF, 25)
                  ->nullable();

            $table->string(FSS::AUTH, 10)
                  ->nullable();

            $table->string(FSS::POST_DATE, 10)
                  ->nullable();

            $table->string(FSS::ERROR_MESSAGE, 255)
                  ->nullable();

            $table->string(FSS::AUTH_RES_CODE, 10)
                  ->nullable();

            $table->integer(FSS::CREATED_AT);

            $table->integer(FSS::UPDATED_AT);

            $table->foreign(FSS::PAYMENT_ID)
                  ->references(FSS::ID)
                  ->on(Table::PAYMENT)
                  ->on_delete('restrict');

            $table->index(FSS::PAYMENT_ID);

            $table->index(FSS::STATUS);

            $table->index(FSS::ACTION);

            $table->index(FSS::RECEIVED);

            $table->index(FSS::CREATED_AT);

            $table->index(FSS::REFUND_ID);

            $table->index(FSS::GATEWAY_PAYMENT_ID);

            $table->index(FSS::GATEWAY_TRANSACTION_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::FSS, function($table)
        {
            $table->dropForeign(Table::FSS . '_' . FSS::PAYMENT_ID . '_foreign');
        });

        Schema::drop(Table::FSS);
    }
}
