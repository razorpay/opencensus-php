<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;

use RZP\Models\Card\Entity as Card;
use RZP\Models\Merchant;

class CreateCards extends Migration
{
    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CARD, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Card::ID, Card::ID_LENGTH)
                  ->primary();

            $table->char(Card::MERCHANT_ID, Card::ID_LENGTH);

            $table->string(Card::NAME)
                  ->nullable()
                  ->default(null);

            $table->char(Card::EXPIRY_MONTH, 2)
                  ->nullable()
                  ->default(null);

            $table->char(Card::EXPIRY_YEAR, 4)
                  ->nullable()
                  ->default(null);

            $table->char(Card::IIN, 6)
                  ->nullable()
                  ->default(null);

            $table->char(Card::TOKEN_IIN, 9)
                  ->nullable()
                  ->default(null);

            $table->char(Card::TOKEN_LAST_4, 4)
                ->nullable()
                ->default(null);

            $table->char(Card::LAST4, 4);

            $table->char(Card::LENGTH, 2);

            $table->string(Card::NETWORK);

            $table->string(Card::TYPE, 7);

            $table->string(Card::SUBTYPE, 255)
                  ->nullable();

            $table->string(Card::CATEGORY, 255)
                  ->nullable();

            $table->string(Card::ISSUER, 100)
                  ->nullable();

            $table->tinyInteger(Card::INTERNATIONAL)
                  ->nullable();

            $table->tinyInteger(Card::EMI)
                  ->nullable();

            $table->string(Card::VAULT, 20)
                   ->nullable();

            $table->string(Card::VAULT_TOKEN, 50)
                   ->nullable();

            $table->string(Card::GLOBAL_FINGERPRINT, 50)
                   ->nullable();

            $table->tinyInteger(Card::REFERENCE1)
                   ->nullable();

            $table->integer(Card::REFERENCE2)
                   ->nullable();

            $table->char(Card::REFERENCE3, Card::ID_LENGTH)
                   ->nullable();

            $table->string(Card::REFERENCE4)
                   ->nullable();

            $table->string(Card::TRIVIA)
                  ->nullable();

            $table->char(Card::COUNTRY, Card::COUNTRY_LENGTH)
                  ->nullable();

            $table->string(Card::GLOBAL_CARD_ID, Card::ID_LENGTH)
                  ->nullable();

            $table->char(Card::TOKEN_EXPIRY_MONTH, 2)
                  ->nullable()
                  ->default(null);

            $table->char(Card::TOKEN_EXPIRY_YEAR, 4)
                 ->nullable()
                 ->default(null);

            $table->char(Card::PROVIDER_REFERENCE_ID, 14)
                 ->nullable()
                 ->default(null);

            // Adds created_at and updated_at columns to the table
            $table->integer(Card::CREATED_AT);
            $table->integer(Card::UPDATED_AT);

            $table->index(Card::IIN);
            $table->index(Card::NETWORK);
            $table->index(Card::LAST4);
            $table->index(Card::VAULT);
            $table->index(Card::VAULT_TOKEN);
            $table->index(Card::GLOBAL_FINGERPRINT);
            $table->index(Card::PROVIDER_REFERENCE_ID);

            $table->index(Card::INTERNATIONAL);

            $table->foreign(Card::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->index(Card::CREATED_AT);
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::CARD, function($table)
        {
            $table->dropForeign(
                Table::CARD . '_' . Card::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::CARD);
    }

}
