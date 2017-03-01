<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('sessions');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('sessions', function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->string('id')->unique();

            $table->text('payload');

            $table->integer('last_activity');

            $table->char('user_id', 14)->nullable();

            $table->string('ip_address')->nullable();

            $table->string('user_agent');

            $table->integer('admin_id')->nullable();
        });
    }
}
