<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\BvsValidation\Constants;

class CreateBvsValidationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BVS_VALIDATION, function(Blueprint $table) {

            $table->char(Entity::VALIDATION_ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::ARTEFACT_TYPE, 255);

            $table->enum(Entity::PLATFORM, Constants::PLATFORMS);

            $table->enum(Entity::VALIDATION_STATUS, Constants::VALIDATION_STATUS);

            $table->enum(Entity::VALIDATION_UNIT, Constants::VALIDATION_UNIT);

            $table->char(Entity::OWNER_ID, Entity::ID_LENGTH);

            $table->char(Entity::OWNER_TYPE, 255);

            $table->char(Entity::ERROR_CODE, 255)
                  ->nullable();

            $table->char(Entity::ERROR_DESCRIPTION, 255)
                  ->nullable();

            $table->json(Entity::RULE_EXECUTION_LIST)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index([Entity::OWNER_ID]);

            $table->integer(Entity::FUZZY_SCORE)
                  ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('_bvs_validation');
    }
}
