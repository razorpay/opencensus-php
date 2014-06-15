<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Field\Key;
use Constants\Field\Common;
use Constants\Table;

class CreateKeys extends Migration {

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::KEYS, function(Blueprint $table){
            $table->engine = 'InnoDB';

            $table->char(Key::ID, Constants\Fields::ID_LENGTH)
                  ->primary();
            
            $table->integer(Common::MERCHANT_ID)
                  ->unsigned();

            $table->string(Key::SECRET, Constants\Fields::KEY_SECRET_HASH_LENTH);

            $table->boolean('live')
                  ->default(0);
                  
            $table->boolean(Key::ACTIVE)
                  ->default(1);
                  
            $table->integer(Common::CREATED_AT);  
            $table->integer(Common::UPDATED_AT);
            $table->integer(Key::EXPIRED_AT)
                  ->nullable();

            $table->foreign(Common::MERCHANT_ID)
                  ->references(Constants\Field\Merchant::ID)
                  ->on(Table::MERCHANTS)
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
        Schema::drop(Table::KEYS);
    }

}