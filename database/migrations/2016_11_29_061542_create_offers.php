<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Offer\Entity as Offer;
use RZP\Models\Order\Entity as Order;

class CreateOffers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::OFFER, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Offer::ID, Offer::ID_LENGTH)
                  ->primary();

            $table->char(Offer::MERCHANT_ID, Offer::ID_LENGTH);

            $table->string(Offer::NAME, 25)->nullable();

            $table->string(Offer::PAYMENT_METHOD, 10);

            $table->string(Offer::PAYMENT_METHOD_TYPE, 6)
                    ->nullable();

            $table->text(Offer::IINS)->nullable();

            $table->string(Offer::PAYMENT_NETWORK, 20)
                    ->nullable();

            $table->string(Offer::ISSUER, 10)
                    ->nullable();

            $table->tinyInteger(Offer::ACTIVE)
                    ->default(1);

            $table->integer(Offer::PERCENT_RATE)
                    ->nullable();

            $table->integer(Offer::MIN_AMOUNT)
                    ->nullable();

            $table->integer(Offer::MAX_CASHBACK)
                    ->nullable();

            $table->integer(Offer::FLAT_CASHBACK)
                    ->nullable();

            $table->integer(Offer::PAYMENT_COUNT)
                    ->nullable();

            $table->integer(Offer::PROCESSING_TIME)->nullable();

            $table->integer(Offer::STARTS_AT);

            $table->integer(Offer::ENDS_AT);

            $table->text(Offer::ADDITIONAL_DETAILS)->nullable();

            $table->string(Offer::CUSTOM_LONG_DISPLAY_TEXT, 200)->nullable();

            $table->string(Offer::CUSTOM_SHORT_DISPLAY_TEXT, 50)->nullable();

            $table->integer(Offer::CREATED_AT);

            $table->integer(Offer::UPDATED_AT);

            $table->index(Offer::PAYMENT_METHOD);

            $table->index(Offer::PAYMENT_METHOD_TYPE);

            $table->index(Offer::PAYMENT_NETWORK);

            $table->index(Offer::ISSUER);

            $table->index(Offer::PERCENT_RATE);

            $table->index(Offer::MIN_AMOUNT);

            $table->index(Offer::MAX_CASHBACK);

            $table->index(Offer::FLAT_CASHBACK);

            $table->index(Offer::STARTS_AT);

            $table->index(Offer::ENDS_AT);

            $table->index(Offer::ACTIVE);

            $table->foreign(Offer::MERCHANT_ID)
                    ->references(Merchant::ID)
                    ->on(Table::MERCHANT)
                    ->on_delete('restrict');
        });

        Schema::table(Table::ORDER, function ($table)
        {
            $table->foreign(Order::OFFER_ID)
                    ->references(Offer::ID)
                    ->on(Table::OFFER)
                    ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::ORDER, function ($table)
        {
            $table->dropForeign(
                Table::ORDER . '_' . Order::OFFER_ID . '_foreign');
        });

        Schema::table(Table::OFFER, function ($table)
        {
            $table->dropForeign(Tbale::OFFER . '_' . Offer::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::OFFER);
    }
}
