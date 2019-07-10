<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Gateway\Paysecure\Entity as Paysecure;
use RZP\Constants\Table;

class CreatePaysecureTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYSECURE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Paysecure::ID);

            $table->char(Paysecure::PAYMENT_ID, Paysecure::ID_LENGTH);

            $table->string(Paysecure::ACTION, 10)
                  ->nullable();

            $table->tinyInteger(Paysecure::RECEIVED)
                  ->default(0);

            $table->string(Paysecure::REFUND_ID, Paysecure::ID_LENGTH)
                  ->nullable();

            $table->string(Paysecure::STATUS, 20)
                  ->nullable();

            $table->string(Paysecure::GATEWAY_TRANSACTION_ID, 30)
                  ->nullable();

            $table->string(Paysecure::ERROR_CODE, 50)
                  ->nullable();

            $table->string(Paysecure::ERROR_MESSAGE, 255)
                  ->nullable();

            $table->string(Paysecure::RRN, 255)
                  ->nullable();

            $table->string(Paysecure::TRAN_DATE, 4);

            $table->string(Paysecure::TRAN_TIME, 6);

            $table->string(Paysecure::FLOW, 10)
                  ->nullable();

            $table->text(Paysecure::HKEY)
                  ->nullable();

            $table->string(Paysecure::AUTH_NOT_REQUIRED, 5)
                  ->nullable();

            $table->string(Paysecure::APPRCODE, 10)
                  ->nullable();

            $table->integer(Paysecure::CREATED_AT);

            $table->integer(Paysecure::UPDATED_AT);

            $table->index(Paysecure::STATUS);

            $table->index(Paysecure::PAYMENT_ID);

            $table->index(Paysecure::RECEIVED);

            $table->index(Paysecure::CREATED_AT);

            $table->index(Paysecure::REFUND_ID);

            $table->index(Paysecure::RRN);

            $table->index(Paysecure::GATEWAY_TRANSACTION_ID);

            $table->index(Paysecure::ACTION);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::PAYSECURE);
    }
}
