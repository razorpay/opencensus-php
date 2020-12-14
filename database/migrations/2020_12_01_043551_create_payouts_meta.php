<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\PayoutMeta\Entity;
use RZP\Models\Payout\Entity as Payout;

class CreatePayoutsMeta extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYOUTS_META, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::PAYOUT_ID, Payout::ID_LENGTH);

            $table->char(Entity::PARTNER_ID, Entity::ID_LENGTH);

            $table->char(Entity::APPLICATION_ID, Entity::ID_LENGTH);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            // indices
            $table->index(Entity::PAYOUT_ID);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::PAYMENT_META);
    }
}
