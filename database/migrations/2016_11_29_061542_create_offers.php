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

            $table->string(Offer::NAME, Offer::NAME_LENGTH)
                  ->nullable();

            $table->string(Offer::PAYMENT_METHOD, Offer::PAYMENT_METHOD_LENGTH)
                 ->nullable();

            $table->string(Offer::PAYMENT_METHOD_TYPE, Offer::PAYMENT_METHOD_TYPE_LENTH)
                  ->nullable();

            $table->text(Offer::IINS)->nullable();

            $table->string(Offer::PAYMENT_NETWORK, Offer::PAYMENT_NETWORK_LENGTH)
                  ->nullable();

            $table->string(Offer::ISSUER, Offer::ISSUER_LENGTH)
                  ->nullable();

            $table->tinyInteger(Offer::INTERNATIONAL)
                  ->nullable();

            $table->tinyInteger(Offer::ACTIVE)
                  ->default(1);

            $table->tinyInteger(Offer::BLOCK)
                  ->default(1);

            $table->tinyInteger(Offer::CHECKOUT_DISPLAY)
                    ->default(0);

            $table->string(Offer::TYPE, 20)
                  ->default(Offer::INSTANT);

            $table->integer(Offer::PERCENT_RATE)
                  ->nullable();

            $table->integer(Offer::MIN_AMOUNT)
                  ->nullable();

            $table->integer(Offer::MAX_CASHBACK)
                  ->nullable();

            $table->integer(Offer::FLAT_CASHBACK)
                  ->nullable();

            $table->tinyInteger(Offer::EMI_SUBVENTION)
                  ->nullable();

            $table->string(Offer::EMI_DURATIONS)
                  ->nullable();

            $table->integer(Offer::MAX_PAYMENT_COUNT)
                  ->nullable();

            $table->integer(Offer::MAX_OFFER_USAGE)
                  ->nullable();

            $table->integer(Offer::CURRENT_OFFER_USAGE)
                  ->nullable();

            $table->integer(Offer::MAX_ORDER_AMOUNT)
                  ->nullable();

            $table->text(Offer::LINKED_OFFER_IDS)
                  ->nullable();

            $table->integer(Offer::PROCESSING_TIME)
                  ->nullable();

            $table->integer(Offer::STARTS_AT);

            $table->integer(Offer::ENDS_AT);

            $table->string(Offer::DISPLAY_TEXT)
                  ->nullable();

            $table->string(Offer::ERROR_MESSAGE)
                  ->nullable();

            $table->text(Offer::TERMS);

            $table->string(Offer::PRODUCT_TYPE, 20)
                  ->nullable();

            $table->integer(Offer::CREATED_AT);

            $table->integer(Offer::UPDATED_AT);

            $table->index(Offer::PAYMENT_METHOD);

            $table->index(Offer::PAYMENT_METHOD_TYPE);

            $table->index(Offer::PAYMENT_NETWORK);

            $table->index(Offer::ISSUER);

            $table->index(Offer::STARTS_AT);

            $table->index(Offer::ENDS_AT);

            $table->index(Offer::ACTIVE);

            $table->index(Offer::CHECKOUT_DISPLAY);

            $table->tinyInteger(Offer::DEFAULT_OFFER)
                  ->default(0);

            $table->foreign(Offer::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->index(Offer::CREATED_AT);
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
            $table->dropForeign(Table::OFFER . '_' . Offer::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::OFFER);
    }
}
