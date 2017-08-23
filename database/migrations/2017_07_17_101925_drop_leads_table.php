<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('leads');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('leads', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->string('email', 250)->unique();

            $table->boolean('registered')->nullable();

            $table->integer('registered_at')->nullable();

            $table->integer('created_at');

            $table->integer('updated_at');
        });
    }
}
