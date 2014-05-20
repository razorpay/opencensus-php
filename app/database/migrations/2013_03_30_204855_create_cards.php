<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCards extends Migration {

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cards', function(Blueprint $table){
                
            $table->engine = 'InnoDB';
    
            $table->increments('id');

            $table->string('name');

            $table->string('cvv', 4);

            $table->string('expiry_month', 2);

            $table->string('expiry_year', 4);

            $table->string('last4', 4);

            $table->string('type');

            /**
             * Two letter ISO codes representing the country of the card.
             */
            $table->string('country', 2);

            $table->string('address_line1')
                  ->nullable();

            $table->string('address_line2')
                  ->nullable();

            $table->string('address_city')
                  ->nullable();
            
            $table->string('address_state')
                  ->nullable();

            $table->integer('address_zip')
                  ->unsigned()
                  ->nullable();

            $table->string('address_country')
                  ->nullable();

            $table->boolean('cvv_check')
                  ->nullable();

            $table->boolean('address_line1_check')
                  ->nullable();

            $table->boolean('address_zip_check')
                  ->nullable();

            $table->timestamps();

        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cards');
    }

}