<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Merchant;
use Models\User\Entity as User;

class CreateUsers extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::USER, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(User::ID, 14)
                  ->primary();

            $table->char(User::MERCHANT_ID, 14);

            $table->char(User::NAME, 50)
                  ->nullable();

            $table->char(User::EMAIL, 50);

            
            $table->char(User::CONTACT, 15);

            $table->integer(User::CREATED_AT);
            $table->integer(User::UPDATED_AT);

            $table->index(User::EMAIL);

            $table->index(User::CONTACT);

            $table->foreign(User::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::USER, function($table)
        {
            $table->dropForeign(Table::USER.'_'.User::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::USER);
    }

}
