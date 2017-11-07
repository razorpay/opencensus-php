<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('users');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('users', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char('id', 14)->primary();

            $table->string('name', 200);

            $table->string('email', 255)->unique();

            $table->string('password', 100);

            $table->string('contact_mobile')->nullable();

            $table->string('remember_token', 100)->nullable();

            $table->string('confirm_token')->nullable();

            $table->integer('created_at');

            $table->integer('updated_at');
        });
    }
}
