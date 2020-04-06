<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Constants\Table;
use RZP\Models\FeeRecovery\Entity;

class CreateFeeRecovery extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::FEE_RECOVERY, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::ENTITY_ID, Base\PublicEntity::ID_LENGTH);

            $table->string(Entity::ENTITY_TYPE, 255);

            $table->string(Entity::TYPE, 255);

            $table->char(Entity::RECOVERY_PAYOUT_ID, Payout\Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::STATUS, 255);

            $table->tinyInteger(Entity::ATTEMPT_NUMBER)
                  ->default(0);

            $table->string(Entity::REFERENCE_NUMBER, 255)
                  ->nullable();

            $table->string(Entity::DESCRIPTION, 255)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::UPDATED_AT);

            $table->index(Entity::ENTITY_ID);

            $table->index(Entity::STATUS);

            $table->index(Entity::RECOVERY_PAYOUT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::FEE_RECOVERY);
    }
}
