<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;

use Models\Merchant\Entity as Merchant;

class CreateMerchants extends Migration {

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->integer(Merchant::ID)
                  ->unsigned()
                  ->primary();

            $table->integer(Merchant::BALANCE)
                  ->default(0);

            $table->integer(Merchant::CREATED_AT);
            $table->integer(Merchant::UPDATED_AT);
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::MERCHANT);
    }

}