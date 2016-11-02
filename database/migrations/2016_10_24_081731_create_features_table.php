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

            $table->increments(Feature::ID);

            $table->string(Feature::NAME, 25);
            $table->char(Feature::ENTITY_ID, Feature::ID_LENGTH);
            $table->string(Feature::ENTITY_TYPE, 255);

            $table->integer(Feature::CREATED_AT);
            $table->integer(Feature::UPDATED_AT);

            $table->index(Feature::CREATED_AT);
            $table->index(Feature::ENTITY_ID);
            $table->index(Feature::ENTITY_TYPE);
            $table->index(Feature::NAME);
            $table->unique(array(Feature::NAME, Feature::ENTITY_ID,
                    Feature::ENTITY_TYPE));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('features');
    }
}
