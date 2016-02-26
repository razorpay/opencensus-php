<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Card;
use Models\Merchant;
use Models\User;
use Models\User\Methods\Entity as Methods;

class CreateUsersMethods extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::USER_METHODS, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Methods::ID, 14)
                  ->primary();

            $table->char(Methods::USER_ID, 14);

            $table->char(Methods::TYPE, 10);
  
            $table->char(Methods::CARD_ID, 14);

            $table->char(Methods::BANK, 4);

            $table->char(Methods::WALLET, 15);

            $table->integer(Methods::CREATED_AT);
            
            $table->integer(Methods::UPDATED_AT);

            $table->index(Methods::USER_ID);

            $table->foreign(Methods::USER_ID)
                  ->references(User\Entity::ID)
                  ->on(Table::USER)
                  ->on_delete('restrict');

            $table->foreign(Methods::CARD_ID)
                  ->references(Card\Entity::ID)
                  ->on(Table::CARD)
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
        Schema::table(Table::USER_METHODS, function($table)
        {
            $table->dropForeign(Table::USER_METHODS.'_'.User::USER_ID.'_foreign');

            $table->dropForeign(Table::USER_METHODS.'_'.User::CARD_ID.'_foreign');
        });

        Schema::drop(Table::USER_METHODS);
    }
}