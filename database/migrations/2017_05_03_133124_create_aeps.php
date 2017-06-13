<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Gateway\Aeps\Base\Entity as Aeps;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Payment\Refund\Entity as Refund;

class CreateAeps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::AEPS, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Aeps::ID);

            $table->string(Aeps::ACQUIRER, 20);

            $table->string(Aeps::PAYMENT_ID, Payment::ID_LENGTH);

            $table->string(Aeps::REFUND_ID, Refund::ID_LENGTH)
                  ->nullable();

            $table->string(Aeps::ACTION);

            $table->integer(Aeps::AMOUNT);

            $table->string(Aeps::ERROR_CODE)
                  ->nullable();

            $table->string(Aeps::ERROR_DESCRIPTION)
                  ->nullable();

            $table->string(Aeps::AADHAAR_NUMBER)
                  ->nullable();

            $table->tinyInteger(Aeps::REVERSED)
                  ->default(0);

            $table->string(Aeps::COUNTER)
                  ->nullable();

            $table->string(Aeps::RRN)
                  ->nullable();

            $table->tinyInteger(Aeps::RECEIVED)
                  ->default(0);

            $table->integer(Aeps::CREATED_AT);

            $table->integer(Aeps::UPDATED_AT);

            $table->foreign(Aeps::PAYMENT_ID)
                  ->references(Payment::ID)
                  ->on(Table::PAYMENT)
                  ->on_delete('restrict');

            $table->index(Aeps::CREATED_AT);
            $table->index(Aeps::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::AEPS, function($table)
        {
            $table->dropForeign(Table::AEPS . '_' . AEPS::PAYMENT_ID . '_foreign');
        });

        Schema::drop(Table::AEPS);
    }
}
