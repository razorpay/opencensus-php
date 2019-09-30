<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\EntityOrigin\Entity;

class CreateEntityOriginsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ENTITY_ORIGIN, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::ORIGIN_ID, Entity::ID_LENGTH);

            $table->string(Entity::ORIGIN_TYPE);

            $table->char(Entity::ENTITY_ID, Entity::ID_LENGTH);

            $table->string(Entity::ENTITY_TYPE);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->unique([Entity::ENTITY_ID, Entity::ENTITY_TYPE]);

            $table->index([Entity::ORIGIN_ID, Entity::ORIGIN_TYPE]);

            $table->index(Entity::CREATED_AT);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::ENTITY_ORIGIN);
    }
}
