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

            $table->char(Analytics::CHECKOUT_ID, Analytics::ID_LENGTH)
                  ->nullable();

            $table->char(Analytics::TERMINAL_ID, Analytics::ID_LENGTH);

            $table->boolean(Analytics::TERMINAL_STATUS)
                ->default(1);

            $table->double(Analytics::TERMINAL_RESPONSE_TIME,8,5)
                ->default(0);

            $table->integer(Analytics::TERMINAL_STATUS_CODE)
                ->default(0);

            $table->text(Analytics::TERMINAL_STATUS_MSG)
                ->nullable();

            $table->tinyInteger(Analytics::PAYMENT_TYPE)
                ->default(0);

            $table->smallInteger(Analytics::ATTEMPTS)
                  ->nullable();

            $table->tinyInteger(Analytics::LIBRARY)
                  ->nullable();

            $table->char(Analytics::LIBRARY_VERSION)
                  ->nullable();

            $table->tinyInteger(Analytics::BROWSER)
                  ->nullable();

            $table->tinyInteger(Analytics::OS)
                  ->nullable();

            $table->char(Analytics::OS_VERSION)
                  ->nullable();

            $table->tinyInteger(Analytics::DEVICE)
                  ->nullable();

            $table->tinyInteger(Analytics::PLATFORM)
                  ->nullable();

            $table->char(Analytics::PLATFORM_VERSION)
                  ->nullable();

            $table->tinyInteger(Analytics::INTEGRATION)
                  ->nullable();

            $table->char(Analytics::INTEGRATION_VERSION)
                  ->nullable();

            // http://stackoverflow.com/questions/1076714/max-length-for-client-ip-address
            $table->char(Analytics::IP, 45)
                  ->nullable();

            $table->char(Analytics::REFERER)
                  ->nullable();

            $table->text(Analytics::USER_AGENT)
                  ->nullable();

            $table->integer(Analytics::CREATED_AT);

            $table->integer(Analytics::UPDATED_AT);

            $table->foreign(Analytics::TERMINAL_ID)
                ->references(Terminal\Entity::ID)
                ->on(Table::TERMINAL)
                ->on_delete('restrict');

            $table->foreign(Analytics::PAYMENT_ID)
                ->references(Payment\Entity::ID)
                ->on(Table::PAYMENT)
                ->on_delete('restrict');

            $table->index(Analytics::CHECKOUT_ID);

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