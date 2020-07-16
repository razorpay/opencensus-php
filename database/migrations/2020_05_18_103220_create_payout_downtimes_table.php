<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Constants\Table;
use RZP\Models\PayoutDowntime\Entity;
use \RZP\Models\PayoutDowntime\Constants;

class CreatePayoutDowntimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYOUT_DOWNTIMES, function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::STATUS, 255);

            $table->string(Entity::CHANNEL, 255);

            $table->string(Entity::MODE, 255)
                  ->nullable();

            $table->integer(Entity::START_TIME)
                  ->nullable();

            $table->integer(Entity::END_TIME)
                  ->nullable();

            $table->text(Entity::DOWNTIME_MESSAGE)
                  ->nullable();

            $table->text(Entity::UPTIME_MESSAGE)
                  ->nullable();

            $table->string(Entity::ENABLED_EMAIL_OPTION, 255)
                  ->default(Constants::NO);

            $table->string(Entity::DISABLED_EMAIL_OPTION, 255)
                  ->default(Constants::NO);

            $table->string(Entity::ENABLED_EMAIL_STATUS, 255)
                  ->nullable();

            $table->string(Entity::DISABLED_EMAIL_STATUS, 255)
                  ->nullable();

            $table->string(Entity::CREATED_BY, 255);

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
        Schema::dropIfExists(Table::PAYOUT_DOWNTIMES);
    }
}
