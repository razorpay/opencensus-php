<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\PayoutSource\Entity;
use RZP\Models\Payout\Entity as Payout;

class CreatePayoutSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYOUT_SOURCE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::SOURCE_ID, 255);

            $table->string(Entity::SOURCE_TYPE, 255);

            $table->char(Entity::PAYOUT_ID, Payout::ID_LENGTH);

            $table->index([Entity::SOURCE_ID, Entity::SOURCE_TYPE]);

            $table->index([Entity::PAYOUT_ID, Entity::PRIORITY]);

            $table->unsignedInteger(Entity::PRIORITY);

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
        Schema::drop(Table::PAYOUT_SOURCE);
    }
}
