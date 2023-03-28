<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\PayoutsDetails\Entity;
use RZP\Models\Payout\Entity as Payout;

class CreatePsPayoutDetails extends Migration
{
    /**
     * This table doesn't exist on prod. It only exists on CI.
     * This is only to run test cases related to data migration of Payouts.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ps_payout_details', function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Payout::ID, Payout::ID_LENGTH)
                  ->primary();

            $table->char(Entity::PAYOUT_ID, Payout::ID_LENGTH);

            $table->tinyInteger(Entity::QUEUE_IF_LOW_BALANCE_FLAG);

            $table->string(Entity::TAX_PAYMENT_ID)
                  ->nullable()
                  ->default(null);

            $table->unsignedInteger(Entity::TDS_CATEGORY_ID)
                  ->nullable()
                  ->default(null);

            $table->json(Entity::ADDITIONAL_INFO)
                  ->nullable()
                  ->default(null);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ps_payout_details');
    }
}
