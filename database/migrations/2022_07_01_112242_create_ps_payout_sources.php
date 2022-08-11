<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\PayoutSource\Entity;

class CreatePsPayoutSources extends Migration
{
    /**
     * This table doesn't exist on prod. It only exists on CI.
     * This is only to run test cases related to data migration of Payouts.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ps_payout_sources', function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::SOURCE_ID, 255);

            $table->string(Entity::SOURCE_TYPE, 255);

            $table->char(Entity::PAYOUT_ID, Entity::ID_LENGTH);

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
        Schema::drop('ps_payout_sources');
    }
}
