<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Survey\Tracker\Entity;

class CreateSurveyTrackerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SURVEY_TRACKER, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)->primary();

            $table->char(Entity::SURVEY_ID, Entity::ID_LENGTH);

            $table->string(Entity::SURVEY_EMAIL, 255);

            $table->integer(Entity::SURVEY_SENT_AT);

            $table->integer(Entity::SURVEY_FILLED_AT)->nullable();

            $table->integer(Entity::ATTEMPTS);

            $table->tinyInteger(Entity::SKIP_IN_APP)->default(0);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::SURVEY_EMAIL);

            $table->index(Entity::SURVEY_SENT_AT);

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
        Schema::drop(Table::SURVEY_TRACKER);
    }
}
