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

            $table->unique([self::MERCHANT_ID, self::OFFER_ID]);
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
            // We are removing this because mysql thinks of merchant_id foreign constraint
            // as equivalent of unique constraint on composer unique key of merchant_id
            // and offer_id. Thus it overrides the constraint key for foreign key
            // Refer: http://stackoverflow.com/questions/5312083/mysql-unique-foreign-key
            // $table->dropForeign(Table::MERCHANT_OFFER.'_'.self::MERCHANT_ID.'_foreign');

            $table->dropForeign(Table::MERCHANT_OFFER.'_'.self::OFFER_ID.'_foreign');
        });

        Schema::drop(Table::MERCHANT_OFFER);
    }
}
