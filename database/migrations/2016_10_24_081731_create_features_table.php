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
            $table->char(Feature::TOGGLEABLE_ID, Feature::ID_LENGTH);
            $table->string(Feature::TOGGLEABLE_TYPE, 255);

            $table->integer(Feature::CREATED_AT);
            $table->integer(Feature::UPDATED_AT);

            $table->index(Feature::CREATED_AT);
            $table->index(Feature::TOGGLEABLE_ID);
            $table->index(Feature::TOGGLEABLE_TYPE);
            $table->index(Feature::NAME);
            $table->unique(array(Feature::NAME, Feature::TOGGLEABLE_ID,
                    Feature::TOGGLEABLE_TYPE));
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
