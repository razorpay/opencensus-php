<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Card\Entity as Card;
use RZP\Models\Customer\Entity as Customer;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Terminal\Entity as Terminal;
use RZP\Models\Customer\Token\Entity as Token;

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

            $table->text(Token::GATEWAY_TOKEN)
                  ->nullable();

            $table->text(Token::GATEWAY_TOKEN2)
                  ->nullable();

            $table->boolean(Token::RECURRING)
                  ->default(0);

            $table->integer(Token::USED_COUNT)
                  ->default(0);

            $table->integer(Token::USED_AT)
                  ->nullable();

            $table->integer(Token::EXPIRED_AT)
                  ->nullable();

            $table->integer(Token::CREATED_AT);

            $table->integer(Token::UPDATED_AT);

            $table->integer(Token::DELETED_AT)
                  ->nullable();

            $table->index(Token::CREATED_AT);

            $table->foreign(Token::CUSTOMER_ID)
                  ->references(Customer::ID)
                  ->on(Table::CUSTOMER)
                  ->on_delete('restrict');

            $table->foreign(Token::CARD_ID)
                  ->references(Card::ID)
                  ->on(Table::CARD)
                  ->on_delete('restrict');

            $table->foreign(Token::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Token::TERMINAL_ID)
                  ->references(Terminal::ID)
                  ->on(Table::TERMINAL)
                  ->on_delete('restrict');

        });

        Schema::table(Table::PAYMENT, function($table)
        {
            $table->foreign(Payment::TOKEN_ID)
                  ->references(Token::ID)
                  ->on(Table::TOKEN)
                  ->on_delete('restrict');

            $table->foreign(Payment::GLOBAL_TOKEN_ID)
                  ->references(Token::ID)
                  ->on(Table::TOKEN)
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
        Schema::table(Table::PAYMENT, function($table)
        {
            $table->dropForeign(Table::PAYMENT.'_'.Payment::TOKEN_ID.'_foreign');

            $table->dropForeign(Table::PAYMENT.'_'.Payment::GLOBAL_TOKEN_ID.'_foreign');
        });


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
