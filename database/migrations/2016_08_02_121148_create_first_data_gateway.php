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

            $table->char(FirstData::ACTION, 10)->nullable();

            $table->integer(FirstData::RECEIVED)->default(0);

            $table->char(FirstData::REFUND_ID, FirstData::ID_LENGTH)->nullable();

            $table->char(FirstData::TXNDATETIME, 20)->nullable();

            $table->char(FirstData::TXNDATE_PROCESSED, 20)->nullable();

            $table->char(FirstData::HASH, 64)->nullable();

            $table->char(FirstData::AMOUNT);

            $table->char(FirstData::PAYMENT_METHOD, 2)->nullable();

            $table->char(FirstData::STATUS, 20)->nullable();

            $table->char(FirstData::OID, 20)->nullable();

            $table->char(FirstData::TDATE, 20)->nullable();

            $table->char(FirstData::REFNUMBER, 20)->nullable();

            $table->char(FirstData::TERMINAL_ID, 20)->nullable();

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

            $table->index(FirstData::REFNUMBER);

            $table->index(FirstData::TDATE);

            $table->index(FirstData::OID);
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
            $table->dropForeign(Table::FIRST_DATA.'_'.FirstData::PAYMENT_ID.'_foreign');
        });

        Schema::drop(Table::FIRST_DATA);
    }
}
