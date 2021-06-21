<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Merchant\Escalations\Actions\Entity;

class CreateMerchantOnboardingEscalationActions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ONBOARDING_ESCALATION_ACTIONS, function (BluePrint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::ESCALATION_ID, Entity::ID_LENGTH);

            $table->string(Entity::ACTION_HANDLER, 255)
                ->nullable();

            $table->string(Entity::STATUS, 30)
                ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::ESCALATION_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::ONBOARDING_ESCALATION_ACTIONS);
    }
}
