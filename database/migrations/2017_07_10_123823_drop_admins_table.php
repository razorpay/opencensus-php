<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropAdminsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('admins');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('admins', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->string('email', 255)->unique();

            $table->string('name', 200);

            $table->string('username', 50)->unique();

            $table->string('password', 100);

            $table->string('remember_token')->nullable();

            $table->boolean('superadmin')->default(0);

            $table->integer('created_at');

            $table->integer('updated_at');

            $table->string('access_token')->nullable();

            $table->string('google_id')->nullable();
        });
    }
}
