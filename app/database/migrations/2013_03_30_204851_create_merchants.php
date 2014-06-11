<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMerchants extends Migration {

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchants', function(Blueprint $table){
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->string('email', Constants\Fields::ID_LENGTH)
                  ->unique();

            $table->string('hash', 100); // For storing passwords after encrypting them.

            $table->integer('created_at');  
            $table->integer('updated_at');
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('merchants');
    }

}