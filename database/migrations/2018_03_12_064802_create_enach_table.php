<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Payment\Entity as Payment;
use RZP\Gateway\Enach\Base\Entity as Enach;
use RZP\Models\Payment\Refund\Entity as Refund;

class CreateEnachTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ENACH, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Enach::ID);

            $table->char(Enach::PAYMENT_ID, Payment::ID_LENGTH);

            $table->char(Enach::REFUND_ID, Refund::ID_LENGTH)
                  ->nullable();

            $table->char(Enach::AUTHENTICATION_GATEWAY)
                  ->nullable();

            $table->string(Enach::ACTION);

            $table->string(Enach::ACQUIRER);

            $table->char(Enach::BANK, 10);

            $table->integer(Enach::AMOUNT);

            $table->string(Enach::STATUS)
                  ->nullable();

            $table->longText(Enach::SIGNED_XML)
                  ->nullable();

            $table->string(Enach::UMRN)
                  ->nullable();

            $table->string(Enach::GATEWAY_REFERENCE_ID)
                  ->nullable();

            $table->string(Enach::GATEWAY_REFERENCE_ID2)
                  ->nullable();

            $table->string(Enach::ACKNOWLEDGE_STATUS)
                  ->nullable();

            $table->string(Enach::REGISTRATION_STATUS)
                  ->nullable();

            $table->integer(Enach::REGISTRATION_DATE)
                  ->nullable();

            $table->string(Enach::ERROR_MESSAGE)
                  ->nullable();

            $table->string(Enach::ERROR_CODE)
                  ->nullable();

            $table->integer(Enach::CREATED_AT);
            $table->integer(Enach::UPDATED_AT);

            $table->foreign(Enach::PAYMENT_ID)
                  ->references(Payment::ID)
                  ->on(Table::PAYMENT)
                  ->on_delete('restrict');

            $table->index(Enach::UMRN);
            $table->index(Enach::GATEWAY_REFERENCE_ID);
            $table->index(Enach::REGISTRATION_DATE);
            $table->index(Enach::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::ENACH, function($table)
        {
            $table->dropForeign(Table::ENACH . '_' . Enach::PAYMENT_ID . '_foreign');
        });

        Schema::drop(Table::ENACH);
    }
}
