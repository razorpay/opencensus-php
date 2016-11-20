<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Payment\Entity as Payment;
use RZP\Gateway\Upi\Base\Entity as Upi;
use RZP\Constants\Table;

class CreateUpi extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create(Table::UPI, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Upi::ID);

            $table->char(Upi::PAYMENT_ID, Payment::ID_LENGTH);

            $table->string(Upi::ACTION);

            $table->string(Upi::AMOUNT);

            $table->string(Upi::GATEWAY);

            $table->string(Upi::BANK)->nullable();

            $table->string(Upi::PROVIDER)->nullable();

            $table->string(Upi::CONTACT)->nullable();

            $table->string(Upi::EMAIL)->nullable();

            $table->string(Upi::VPA)->nullable();

            $table->string(Upi::NAME)->nullable();

            $table->tinyInteger(Upi::RECEIVED)->default(0);

            $table->string(Upi::GATEWAY_MERCHANT_ID)->nullable();

            $table->string(Upi::GATEWAY_PAYMENT_ID)->nullable();

            $table->string(Upi::STATUS_CODE)->nullable();

            $table->integer(Upi::CREATED_AT);
            $table->integer(Upi::UPDATED_AT);

            $table->foreign(Upi::PAYMENT_ID)
                  ->references(Payment::ID)
                  ->on(Table::PAYMENT)
                  ->on_delete('restrict');

            $table->index(Upi::RECEIVED);
            $table->index(Upi::GATEWAY_PAYMENT_ID);
            $table->index(Upi::BANK);
            $table->index(Upi::STATUS_CODE);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::UPI, function($table)
        {
            $table->dropForeign(Table::UPI.'_'.Upi::PAYMENT_ID.'_foreign');
        });

        Schema::drop(Table::UPI);
    }
}
