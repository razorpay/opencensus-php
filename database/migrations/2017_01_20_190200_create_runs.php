<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Schedule\Run\Entity;
use RZP\Constants\Table;
use RZP\Models\Schedule;

class CreateRuns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::RUN, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::SCHEDULE_ID, Entity::ID_LENGTH);

            $table->char(Entity::ENTITY_ID, Entity::ID_LENGTH);

            $table->integer(Entity::LAST_RUN_AT)
                  ->nullable();

            $table->integer(Entity::NEXT_RUN_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);
            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::NEXT_RUN_AT);

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index(Entity::DELETED_AT);

            $table->foreign(Entity::SCHEDULE_ID)
                  ->references(Schedule\Entity::ID)
                  ->on(Table::SCHEDULE)
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::RUN, function($table)
        {
            $table->dropForeign
            (
                Table::RUN . '_' . Entity::SCHEDULE_ID . '_foreign'
            );
        });

        Schema::drop(Table::RUN);
    }
}
