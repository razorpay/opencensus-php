<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Application\ApplicationTags\Entity as AppMapping;

class CreateAppframeworkAppMappingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::APPLICATION_MAPPING, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(AppMapping::ID, AppMapping::ID_LENGTH)->primary();

            $table->string(AppMapping::TAG, 255);

            $table->char(AppMapping::APP_ID, AppMapping::ID_LENGTH);

            $table->integer(AppMapping::CREATED_AT);

            $table->integer(AppMapping::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::APPLICATION_MAPPING);
    }
}
