<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Constants\Table;
use RZP\Models\AccessControlHistoryLogs\Entity;

class AccessControlHistoryLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create(Table::ACCESS_CONTROL_HISTORY_LOGS , function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, 14)
                ->primary();

            $table->char(Entity::ENTITY_ID, 30);

            $table->string(Entity::ENTITY_TYPE, 100);

            $table->char(Entity::OWNER_ID, 14);

            $table->string(Entity::OWNER_TYPE, 100)->nullable();

            $table->string(Entity::MESSAGE, 255)->nullable();

            $table->json(Entity::PREVIOUS_VALUE)->nullable();

            $table->json(Entity::NEW_VALUE)->nullable();

            $table->char(Entity::CREATED_BY, 14);

            $table->bigInteger(Entity::CREATED_AT);

            $table->bigInteger(Entity::UPDATED_AT);

            $table->index(Entity::ENTITY_ID);

            $table->index(Entity::OWNER_ID);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::dropIfExists(Table::ACCESS_CONTROL_HISTORY_LOGS);
    }
}
