<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Payment;
use RZP\Models\Merchant;
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

            $table->char(Analytics::PAYMENT_ID, Analytics::ID_LENGTH);

            $table->char(Analytics::MERCHANT_ID, Analytics::ID_LENGTH);

            $table->string(Analytics::CHECKOUT_ID, Analytics::ID_LENGTH)
                  ->nullable();

            $table->decimal(Analytics::RISK_SCORE, 11, 4)
                  ->nullable();

            $table->tinyInteger(Analytics::RISK_ENGINE)
                  ->nullable();

            $table->smallInteger(Analytics::ATTEMPTS)
                  ->unsigned()
                  ->nullable();

            $table->tinyInteger(Analytics::LIBRARY)
                  ->nullable();

            $table->string(Analytics::LIBRARY_VERSION, 50)
                  ->nullable();

            $table->tinyInteger(Analytics::BROWSER)
                  ->nullable();

            $table->string(Analytics::BROWSER_VERSION, 50)
                  ->nullable();

            $table->tinyInteger(Analytics::OS)
                  ->nullable();

            $table->string(Analytics::OS_VERSION, 50)
                  ->nullable();

            $table->tinyInteger(Analytics::DEVICE)
                  ->nullable();

            $table->tinyInteger(Analytics::PLATFORM)
                  ->nullable();

            $table->string(Analytics::PLATFORM_VERSION, 50)
                  ->nullable();

            $table->tinyInteger(Analytics::INTEGRATION)
                  ->nullable();

            $table->string(Analytics::INTEGRATION_VERSION, 50)
                  ->nullable();

            // http://stackoverflow.com/questions/1076714/max-length-for-client-ip-address
            $table->string(Analytics::IP, 45)
                  ->nullable();

            $table->text(Analytics::REFERER)
                  ->nullable();

            $table->text(Analytics::USER_AGENT)
                  ->nullable();

            $table->string(Analytics::VIRTUAL_DEVICE_ID, 100)
                  ->nullable();

            $table->integer(Analytics::CREATED_AT);

            $table->integer(Analytics::UPDATED_AT);

            $table->foreign(Analytics::PAYMENT_ID)
                ->references(Payment\Entity::ID)
                ->on(Table::PAYMENT)
                ->on_delete('restrict');

            $table->foreign(Analytics::MERCHANT_ID)
                ->references(Merchant\Entity::ID)
                ->on(Table::MERCHANT)
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
                Table::PAYMENT_ANALYTICS.'_'.Analytics::PAYMENT_ID.'_foreign');

            $table->dropForeign(
                Table::PAYMENT_ANALYTICS.'_'.Analytics::MERCHANT_ID.'_foreign');

        });

        Schema::drop(Table::PAYMENT_ANALYTICS);
    }
}
