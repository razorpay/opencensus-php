<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropPasswordRemindersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('password_reminders');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('password_reminders', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string('type')->index();

            $table->string('email')->index();

            $table->string('token')->index();

            $table->integer('created_at');
        });
    }
}
