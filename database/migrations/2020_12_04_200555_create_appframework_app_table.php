<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Application\Entity as App;

class CreateAppframeworkAppTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::APPLICATION, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(App::ID, App::ID_LENGTH)->primary();

            $table->string(App::NAME, 255);

            $table->string(App::TITLE, 255);

            $table->text(App::DESCRIPTION)->nullable();

            $table->string(App::TYPE, 255);

            $table->boolean(App::HOME_APP);

            $table->integer(App::CREATED_AT);

            $table->integer(App::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::APPLICATION);
    }
}
