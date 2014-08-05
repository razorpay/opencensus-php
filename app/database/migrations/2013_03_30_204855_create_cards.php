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

            $table->string(Card::EXPIRY_MONTH, 2);

            $table->string(Card::EXPIRY_YEAR, 4);

            $table->char(Card::LAST4, 4);

            $table->string(Card::NETWORK, 10);

            $table->string(Card::TYPE, 6);

            $table->string(Card::BANK, 100);

            $table->char(Card::COUNTRY, Card::COUNTRY_LENGTH);

            $table->string(Card::ADDRESS_LINE1)
                  ->nullable();

            $table->string(Card::ADDRESS_LINE2)
                  ->nullable();

            $table->string(Card::ADDRESS_CITY)
                  ->nullable();

            $table->string(Card::ADDRESS_STATE)
                  ->nullable();

            $table->integer(Card::ADDRESS_ZIP)
                  ->unsigned()
                  ->nullable();

            $table->string(Card::ADDRESS_COUNTRY)
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