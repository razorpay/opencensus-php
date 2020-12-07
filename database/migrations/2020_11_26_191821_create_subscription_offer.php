<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Offer\SubscriptionOffer\Entity as SubscriptionOffer;

class CreateSubscriptionOffer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SUBSCRIPTION_OFFERS_MASTER, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(SubscriptionOffer::ID, SubscriptionOffer::ID_LENGTH)
                  ->primary();

            $table->char(SubscriptionOffer::OFFER_ID, SubscriptionOffer::ID_LENGTH);

            $table->string(SubscriptionOffer::APPLICABLE_ON, 6);

            $table->string(SubscriptionOffer::REDEMPTION_TYPE, 8);

            $table->integer(SubscriptionOffer::NO_OF_CYCLES)
                  ->unsigned()
                  ->nullable();

            $table->unique([SubscriptionOffer::OFFER_ID]);

            $table->integer(SubscriptionOffer::CREATED_AT);
            $table->integer(SubscriptionOffer::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::SUBSCRIPTION_OFFERS_MASTER);
    }
}
