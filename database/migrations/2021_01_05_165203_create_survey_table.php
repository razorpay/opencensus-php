<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Survey\Entity;

class CreateSurveyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SURVEY, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)->primary();

            $table->string(Entity::NAME, 255);

            $table->text(Entity::DESCRIPTION)->nullable();

            $table->integer(Entity::SURVEY_TTL);

            $table->tinyInteger(Entity::CHANNEL);

            $table->string(Entity::TYPE, 255);

            $table->string(Entity::SURVEY_URL, 255);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::NAME);

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
        Schema::drop(Table::SURVEY);
    }
}
