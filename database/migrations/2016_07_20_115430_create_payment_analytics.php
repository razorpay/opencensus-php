<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Models\Payment\Analytics\Entity as Analytics;

class CreatePaymentAnalytics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYMENT_ANALYTICS, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments(Analytics::ID);

            $table->char(Analytics::PAYMENT_ID, Analytics::ID_LENGTH);

            $table->char(Analytics::TERMINAL_ID, Analytics::ID_LENGTH);

            $table->boolean(Analytics::STATUS)
                ->default(1);

            $table->double(Analytics::TERMINAL_RESPONSE_TIME,8,5)
                ->default(0);

            $table->integer(Analytics::STATUS_CODE)
                ->default(0);

            $table->text(Analytics::STATUS_MSG)
                ->nullable();

            $table->tinyInteger(Analytics::PAYMENT_TYPE)
                ->default(0);

            $table->foreign(Analytics::PAYMENT_ID)
                ->references(Payment\Entity::ID)
                ->on(Table::PAYMENT)
                ->on_delete('restrict');

            $table->foreign(Analytics::TERMINAL_ID)
                ->references(Terminal\Entity::ID)
                ->on(Table::TERMINAL)
                ->on_delete('restrict');


            $table->integer(Analytics::CREATED_AT);

            $table->integer(Analytics::UPDATED_AT);

            $table->index(Analytics::TERMINAL_ID);

            $table->index(Analytics::CREATED_AT);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::PAYMENT_ANALYTICS, function($table)
        {
            $table->dropForeign(
                TABLE::PAYMENT_ANALYTICS.'_'.Analytics::TERMINAL_ID.'_foreign');

            $table->dropForeign(
                TABLE::PAYMENT_ANALYTICS.'_'.Analytics::PAYMENT_ID.'_foreign');
        });

        Schema::drop(Table::PAYMENT_ANALYTICS);
    }
}
