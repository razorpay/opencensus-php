<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Origin\Entity;

class CreateOriginsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ORIGIN, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::ORIGIN_ID, Entity::ID_LENGTH);

            $table->string(Entity::ORIGIN_TYPE);

            $table->char(Entity::ENTITY_ID, Entity::ID_LENGTH)
                  ->unique();

            $table->string(Entity::ENTITY_TYPE);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::ENTITY_ID);

            $table->index(Entity::ORIGIN_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::ORIGIN);
    }
}
