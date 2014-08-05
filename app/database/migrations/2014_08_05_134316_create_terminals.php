<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTerminals extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('terminals', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char('id', 24)
                  ->primary();

            $table->string('terminal_id', 24);

            $table->char('merchant_id', 24);

            $table->string('password');

            $table->string('gateway');

            $table->string('gateway_terminal_id');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }

}
