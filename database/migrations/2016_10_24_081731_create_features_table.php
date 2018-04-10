<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Feature\Entity as Feature;

class CreateFeaturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::FEATURE, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Feature::ID, Feature::ID_LENGTH)
                  ->primary();
            $table->string(Feature::NAME, 25);
            $table->char(Feature::ENTITY_ID, Feature::ID_LENGTH);
            $table->string(Feature::ENTITY_TYPE, 255);

            $table->integer(Feature::CREATED_AT);
            $table->integer(Feature::UPDATED_AT);

            $table->index(Feature::ENTITY_ID);
            $table->index(Feature::ENTITY_TYPE);
            $table->unique([Feature::NAME, Feature::ENTITY_ID]);
            $table->index(Feature::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::FEATURE);
    }
}
