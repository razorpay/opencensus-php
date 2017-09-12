<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Gateway\Blade\Entity as Blade;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Payment\Refund\Entity as Refund;

class CreateBladeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BLADE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Blade::ID);

            $table->char(Blade::ACTION, 10)
                  ->nullable();

            $table->char(Blade::ACQUIRER, 10)
                  ->nullable();

            $table->string(Blade::PAYMENT_ID, Payment::ID_LENGTH);

            $table->char(Blade::REFUND_ID, Refund::ID_LENGTH)
                  ->nullable();

            $table->integer(Blade::AMOUNT);

            $table->string(Blade::CURRENCY, 3)
                  ->nullable();

            $table->char(Blade::STATUS, 1)
                  ->nullable();

            $table->char(Blade::XID, 40)
                  ->nullable();

            $table->char(Blade::CAVV, 40)
                  ->nullable();

            $table->char(Blade::ACC_ID, 40)
                  ->nullable();

            $table->char(Blade::CAVV_ALGORITHM, 1)
                  ->nullable();

            $table->char(Blade::ENROLLED, 1)
                  ->nullable();

            $table->char(Blade::ECI, 2)
                  ->nullable();

            $table->integer(Blade::RECEIVED)
                  ->default(0);

            $table->integer(Blade::CREATED_AT);

            $table->integer(Blade::UPDATED_AT);

            $table->foreign(Blade::PAYMENT_ID)
                  ->references(Blade::ID)
                  ->on(Table::PAYMENT)
                  ->onDelete('restrict');

            $table->index(Blade::STATUS);
            $table->index(Blade::RECEIVED);
            $table->index(Blade::CREATED_AT);
            $table->index(Blade::REFUND_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::BLADE, function($table)
        {
            $table->dropForeign(Table::BLADE . '_' . Blade::PAYMENT_ID . '_foreign');
        });

        Schema::drop(Table::BLADE);
    }
}
