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

class CreateTokens extends Migration
{

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

            $table->char(Token::CUSTOMER_ID, Token::ID_LENGTH)
                  ->nullable();

            $table->char(Token::ENTITY_ID, Token::ID_LENGTH)
                  ->nullable();

            $table->string(Token::ENTITY_TYPE, 255)
                  ->nullable();

            $table->char(Token::MERCHANT_ID, Token::ID_LENGTH);

            $table->char(Token::TERMINAL_ID, Token::ID_LENGTH)
                  ->nullable();

            $table->char(Token::TOKEN, 14)
                  ->unique();

            $table->string(Token::METHOD, 10);

            $table->char(Token::CARD_ID, Token::ID_LENGTH)
                  ->nullable();

            $table->char(Token::CARD_MANDATE_ID, Token::ID_LENGTH)
                  ->nullable();

            $table->char(Token::VPA_ID, Token::ID_LENGTH)
                  ->nullable();

            $table->string(Token::BANK, 6)
                  ->nullable();

            $table->integer(Token::MAX_AMOUNT)
                  ->nullable();

            $table->string(Token::DEBIT_TYPE, 20)
                  ->nullable();

            $table->string(Token::FREQUENCY, 20)
                  ->nullable();

            $table->string(Token::WALLET, 15)
                  ->nullable();

            $table->string(Token::ACCOUNT_NUMBER, 40)
                  ->nullable();

            $table->string(Token::ACCOUNT_TYPE, 255)
                  ->nullable();

            $table->string(Token::BENEFICIARY_NAME, 120)
                  ->nullable();

            $table->string(Token::IFSC, 16)
                  ->nullable();

            $table->string(Token::AADHAAR_NUMBER)
                  ->nullable();

            $table->string(Token::AADHAAR_VID)
                  ->nullable();

            $table->text(Token::GATEWAY_TOKEN)
                  ->nullable();

            $table->text(Token::GATEWAY_TOKEN2)
                  ->nullable();

            $table->char(Token::AUTH_TYPE, 14)
                  ->nullable();

            $table->string(Token::STATUS, 20)
                  ->nullable();

            $table->text(Token::NOTES)
                  ->nullable();

            $table->boolean(Token::RECURRING)
                  ->default(0);

            $table->string(Token::RECURRING_STATUS, 32)
                  ->nullable();

            $table->text(Token::RECURRING_FAILURE_REASON)
                  ->nullable();

            $table->string(Token::INTERNAL_ERROR_CODE,255)
                ->nullable();

            $table->text(Token::ERROR_DESCRIPTION)
                ->nullable();

            $table->integer(Token::START_TIME)
                  ->nullable();

            $table->integer(Token::CONFIRMED_AT)
                  ->nullable();

            $table->integer(Token::REJECTED_AT)
                  ->nullable();

            $table->integer(Token::INITIATED_AT)
                  ->nullable();

            $table->integer(Token::ACKNOWLEDGED_AT)
                  ->nullable();

            $table->integer(Token::USED_COUNT)
                  ->default(0);

            $table->integer(Token::USED_AT)
                  ->nullable();

            $table->bigInteger(Token::EXPIRED_AT)
                  ->nullable();

            $table->integer(Token::CREATED_AT);

            $table->integer(Token::UPDATED_AT);

            $table->integer(Token::DELETED_AT)
                  ->nullable();

            $table->index(Token::CREATED_AT);

            $table->index(Token::ACCOUNT_NUMBER);

            $table->index(Token::RECURRING_STATUS);

            $table->index(Token::CONFIRMED_AT);

            $table->index(Token::REJECTED_AT);

            $table->index(Token::ACKNOWLEDGED_AT);

            $table->index(Token::INITIATED_AT);

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

            $table->index(Token::MERCHANT_ID);

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
