<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Card;
use Models\Merchant;
use Models\Customer;
use Models\Terminal;
use Models\Customer\Token\Entity as Token;

class CreateTokens extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TOKEN, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Token::ID, 14)
                  ->primary();

            $table->char(Token::CUSTOMER_ID, Token::ID_LENGTH);

            $table->char(Token::MERCHANT_ID, Token::ID_LENGTH);

            $table->char(Token::TERMINAL_ID, Token::ID_LENGTH)
                  ->nullable();

            $table->char(Token::TOKEN, 14)
                  ->unique();

            $table->string(Token::METHOD, 10);

            $table->char(Token::CARD_ID, Token::ID_LENGTH)
                  ->nullable();

            $table->string(Token::BANK, 6)
                  ->nullable();

            $table->string(Token::WALLET, 15)
                  ->nullable();

            $table->string(Token::GATEWAY_TOKEN, 50)
                  ->nullable();

            $table->integer(Token::CREATED_AT);

            $table->integer(Token::UPDATED_AT);

            $table->index(Token::CREATED_AT);

            $table->foreign(Token::CUSTOMER_ID)
                  ->references(Customer\Entity::ID)
                  ->on(Table::CUSTOMER)
                  ->on_delete('restrict');

            $table->foreign(Token::CARD_ID)
                  ->references(Card\Entity::ID)
                  ->on(Table::CARD)
                  ->on_delete('restrict');

            $table->foreign(Token::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Token::TERMINAL_ID)
                  ->references(Terminal\Entity::ID)
                  ->on(Table::TERMINAL)
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
        Schema::table(Table::TOKEN, function($table)
        {
            $table->dropForeign(Table::TOKEN.'_'.Token::CUSTOMER_ID.'_foreign');

            $table->dropForeign(Table::TOKEN.'_'.Token::MERCHANT_ID.'_foreign');

            $table->dropForeign(Table::TOKEN.'_'.Token::CARD_ID.'_foreign');

            $table->dropForeign(Table::TOKEN.'_'.Token::TERMINAL_ID.'_foreign');
        });

        Schema::drop(Table::TOKEN);
    }
}