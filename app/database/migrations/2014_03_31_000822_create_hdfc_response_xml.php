<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHdfcResponseXml extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hdfc_response_xml', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string('id', 32)
                  ->primary();

            $table->text('enroll');

            $table->text('auth_enrolled')
                  ->nullable();

            $table->text('auth_not_enrolled')
                  ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hdfc_response_xml', function($table){

            ;
        });

        Schema::drop('hdfc_response_xml');
    }

}
