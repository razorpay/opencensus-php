<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKeys extends Migration {

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('keys', function(Blueprint $table){
            $table->engine = 'InnoDB';

            $table->char('id', Constants\Fields::ID_LENGTH)
                  ->primary();
            
            $table->integer('merchant_id')
                  ->unsigned();

            $table->string('secret', Constants\Fields::KEY_SECRET_HASH_LENTH);

            $table->boolean('live')
                  ->default(0);
                  
            $table->boolean('active')
                  ->default(1);
                  
            $table->integer('created_at');  
            $table->integer('updated_at');
            $table->integer('expired_at')
                  ->nullable();

            $table->foreign('merchant_id')
                  ->references('id')
                  ->on('merchants')
                  ->on_delete('restrict');
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('keys');
    }

}