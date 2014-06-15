<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Field\Merchant;
use Constants\Field\Common;
use Constants\Table;

class CreateMerchants extends Migration {

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANTS, function(Blueprint $table){
            $table->engine = 'InnoDB';

            $table->integer(Merchant::ID)
                  ->unsigned()
                  ->primary();

            $table->integer(Merchant::BALANCE)
                  ->default(0);

            $table->integer(Common::CREATED_AT);  
            $table->integer(Common::UPDATED_AT);
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::MERCHANTS);
    }

}