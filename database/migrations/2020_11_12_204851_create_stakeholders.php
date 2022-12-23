<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Stakeholder\Entity;

class CreateStakeholders extends Migration
{
    public function up()
    {
        Schema::create(Table::STAKEHOLDER, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Merchant\Entity::ID_LENGTH);

            $table->string(Entity::EMAIL)->nullable();
            $table->string(Entity::NAME)->nullable();
            $table->string(Entity::PHONE_PRIMARY)->nullable();
            $table->string(Entity::PHONE_SECONDARY)->nullable();
            $table->tinyInteger(Entity::DIRECTOR)->nullable();
            $table->tinyInteger(Entity::EXECUTIVE)->nullable();
            $table->integer(Entity::PERCENTAGE_OWNERSHIP)->nullable()->unsigned();
            $table->string(Entity::POI_IDENTIFICATION_NUMBER)->nullable();
            $table->string(Entity::POI_STATUS)->nullable();
            $table->string(Entity::PAN_DOC_STATUS)->nullable();
            $table->string(Entity::POA_STATUS)->nullable();
            $table->text(Entity::NOTES)->nullable();

            $table->string(Entity::AADHAAR_ESIGN_STATUS)->nullable();
            $table->string(Entity::AADHAAR_PIN)->nullable();
            $table->boolean(Entity::AADHAAR_LINKED)->default(1);
            $table->string(Entity::BVS_PROBE_ID)->nullable();
            $table->string(Entity::AADHAAR_VERIFICATION_WITH_PAN_STATUS)->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->char(Entity::AUDIT_ID,Entity::ID_LENGTH)->nullable();

            $table->index(Entity::MERCHANT_ID);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::UPDATED_AT);

            $table->json(Entity::VERIFICATION_METADATA)
                  ->nullable();
        });
    }

    public function down()
    {
        Schema::drop(Table::STAKEHOLDER);
    }
}
