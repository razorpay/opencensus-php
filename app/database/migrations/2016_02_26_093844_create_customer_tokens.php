<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Card;
use Models\Merchant;
use Models\Customer;
use Models\Customer\Token\Entity as Token;

class CreateCustomerTokens extends Migration {

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

            $table->char(Token::CUSTOMER_ID, 14);

            $table->char(Token::TOKEN, 14);

            $table->char(Token::METHOD, 10);

            $table->char(Token::CARD_ID, 14)
                  ->nullable();

            $table->char(Token::BANK, 6)
                  ->nullable();

            $table->char(Token::WALLET, 15)
                  ->nullable();

            $table->char(Token::GATEWAY_TOKEN)
                  ->nullable();

            $table->integer(Token::CREATED_AT);

            $table->integer(Token::UPDATED_AT);

            $table->index(Token::TOKEN);
            $table->index(Token::CREATED_AT);

            $table->foreign(Token::CUSTOMER_ID)
                  ->references(Customer\Entity::ID)
                  ->on(Table::CUSTOMER)
                  ->on_delete('restrict');

            $table->foreign(Token::CARD_ID)
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
        Schema::table(Table::TOKEN, function($table)
        {
            $table->dropForeign(Table::TOKEN.'_'.Customer::CUSTOMER_ID.'_foreign');

            $table->dropForeign(Table::TOKEN.'_'.Customer::CARD_ID.'_foreign');
        });

        Schema::drop(Table::TOKEN);
    }
}