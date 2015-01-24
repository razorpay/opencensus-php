<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;

use Models\Card\Entity as Card;

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

            $table->string(Card::NAME);

            $table->char(Card::EXPIRY_MONTH, 2);

            $table->char(Card::EXPIRY_YEAR, 4);

            $table->char(Card::IIN, 6);

            $table->char(Card::LAST4, 4);

            $table->char(Card::LENGTH, 2);

            $table->string(Card::NETWORK);

            $table->string(Card::TYPE, 7);

            $table->string(Card::ISSUER, 100)
                  ->nullable();

            $table->boolean(Card::INTERNATIONAL)
                  ->nullable();

            $table->string(Card::TRIVIA)
                  ->nullable();

            $table->char(Card::COUNTRY, Card::COUNTRY_LENGTH)
                  ->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer(Card::CREATED_AT);
            $table->integer(Card::UPDATED_AT);
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::CARD);
    }

}