<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Offer\Entity as Offer;

class CreateMerchantOffer extends Migration
{
    const OFFER_ID    = 'offer_id';
    const MERCHANT_ID = 'merchant_id';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_OFFER, function (Blueprint $table)
        {
            $table->char(self::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->char(self::OFFER_ID, Offer::ID_LENGTH);

            $table->foreign(self::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT);

            $table->foreign(self::OFFER_ID)
                  ->references(Offer::ID)
                  ->on(Table::OFFER);

            $table->unique(array(self::MERCHANT_ID, self::OFFER_ID));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::MERCHANT_OFFER, function ($table)
        {
            $table->dropForeign(Table::MERCHANT_OFFER.'_'.self::MERCHANT_ID.'_foreign');

            $table->dropForeign(Table::MERCHANT_OFFER.'_'.self::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::MERCHANT_OFFER);
    }
}
