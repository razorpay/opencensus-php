<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Models\Partner\KycAccessState\Entity as Entity;


class CreatePartnerKycAccessState extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PARTNER_KYC_ACCESS_STATE, function(Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::ENTITY_ID, Entity::ID_LENGTH);

            $table->string(Entity::ENTITY_TYPE, 255);

            $table->char(Entity::PARTNER_ID, Entity::ID_LENGTH);

            $table->string(Entity::STATE);

            $table->string(Entity::APPROVE_TOKEN)->nullable();

            $table->string(Entity::REJECT_TOKEN)->nullable();

            $table->integer(Entity::TOKEN_EXPIRY)->nullable();

            $table->tinyInteger(Entity::REJECTION_COUNT)->default(0);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index(Entity::PARTNER_ID);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::PARTNER_KYC_ACCESS_STATE);
    }
}
