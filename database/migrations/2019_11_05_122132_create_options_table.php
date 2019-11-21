<?php

use RZP\Constants\Table;
use RZP\Models\Options\Entity;
use RZP\Models\Options\Constants;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(TABLE::OPTIONS, function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::NAMESPACE);

            $table->char(Entity::REFERENCE_ID)
                ->nullable();

            $table->char(Entity::SERVICE_TYPE);

            $table->text(Entity::OPTIONS_JSON);

            $table->char(Entity::SCOPE)
                ->default(Constants::SCOPE_GLOBAL);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                ->nullable();

            $table->index(Entity::MERCHANT_ID);
            $table->index(Entity::NAMESPACE);
            $table->index(Entity::SERVICE_TYPE);
            $table->index(Entity::REFERENCE_ID);
            $table->index(Entity::CREATED_AT);
            $table->index([Entity::MERCHANT_ID, Entity::NAMESPACE]);
            $table->index([Entity::MERCHANT_ID, Entity::SERVICE_TYPE, Entity::REFERENCE_ID]);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(TABLE::OPTIONS);
    }
}
