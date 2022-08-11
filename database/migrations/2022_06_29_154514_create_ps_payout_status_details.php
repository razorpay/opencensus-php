<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Base\PublicEntity;
use RZP\Models\Payout\Entity as Payout;
use RZP\Models\PayoutsStatusDetails\Entity;

class CreatePsPayoutStatusDetails extends Migration
{
    /**
     * This table doesn't exist on prod. It only exists on CI.
     * This is only to run test cases related to data migration of Payouts.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ps_payout_status_details', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(PublicEntity::ID,Payout::ID_LENGTH)
                  ->primary();

            $table->char(Entity::PAYOUT_ID,Payout::ID_LENGTH);

            $table->string(Entity::STATUS);

            $table->string(Entity::REASON,255)
                  ->nullable();

            $table->string(Entity::DESCRIPTION,255)
                  ->nullable();

            $table->string(Entity::MODE);

            $table->string(Entity::TRIGGERED_BY)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

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
        Schema::dropIfExists('ps_payout_status_details');
    }
}
