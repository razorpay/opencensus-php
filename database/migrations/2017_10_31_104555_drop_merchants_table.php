<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropMerchantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('merchants');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('merchants', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char('id', 14)->primary();

            $table->string('name', 200);

            $table->string('email', 255);

            $table->boolean('activated')->default(0);

            $table->string('remember_token', 100)->nullable();

            $table->integer('created_at');

            $table->integer('updated_at');

            $table->integer('archived_at')->nullable();

            $table->integer('suspended_at')->nullable();

            $table->index('archived_at');

            $table->index('email');

            $table->index('suspended_at');
        });
    }
}
