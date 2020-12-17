<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Offer\Entity as Offer;
use RZP\Models\Offer\EntityOffer\Entity as EntityOffer;

class CreateEntityOffer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ENTITY_OFFER, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(EntityOffer::ENTITY_ID, Offer::ID_LENGTH);

            $table->string(EntityOffer::ENTITY_TYPE);

            $table->char(EntityOffer::OFFER_ID, Offer::ID_LENGTH);

            $table->char(EntityOffer::ENTITY_OFFER_TYPE, 50)
                  ->default('offer');

            $table->unique([EntityOffer::ENTITY_ID, EntityOffer::ENTITY_TYPE, EntityOffer::OFFER_ID]);

            $table->integer(EntityOffer::CREATED_AT);
            $table->integer(EntityOffer::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::ENTITY_OFFER);
    }
}
