<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Token\Entity as Token;
use Models\Merchant;

class CreateTokens extends Migration {

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TOKEN, function($table){
            $table->engine = 'InnoDB';

            $table->char(Token::ID, Token::ID_LENGTH)
                  ->primary();

            $table->char(Token::CARD_ID, Token::ID_LENGTH)
                  ->unique()
                  ->nullable();

            $table->integer(Token::MERCHANT_ID)
                  ->unsigned()
                  ->nullable();

            $table->boolean(TOKEN::EXPIRED);

            // Adds created_at and updated_at columns to the table
            $table->integer(Token::CREATED_AT);
            $table->integer(Token::UPDATED_AT);

            $table->foreign(Token::CARD_ID)
                  ->references(Token::ID)
                  ->on(Table::CARD)
                  ->on_delete('SET NULL');

            $table->foreign(Token::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('SET NULL');
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::TOKEN, function($table)
        {
            $table->dropForeign(Table::TOKEN.'_'.Token::CARD_ID.'_foreign');

            $table->dropForeign(Table::TOKEN.'_'.Token::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::TOKEN);
    }

}