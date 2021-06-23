<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\PayoutsDetails\Entity;
use RZP\Models\Payout\Entity as Payout;

class CreatePayoutsDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYOUTS_DETAILS, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::PAYOUT_ID, Payout::ID_LENGTH)
                  ->primary();

            $table->tinyInteger(Entity::QUEUE_IF_LOW_BALANCE_FLAG)
                  ->default(0);

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
        Schema::dropIfExists(Table::PAYOUTS_DETAILS);
    }
}
