<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Constants\Table;
use RZP\Models\PayoutsStatusDetails\Entity;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Payout\Entity as Payout;

class CreatePayoutsStatusDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYOUTS_STATUS_DETAILS, function (Blueprint $table) {
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
        Schema::dropIfExists('payouts_status_details');
    }
}
