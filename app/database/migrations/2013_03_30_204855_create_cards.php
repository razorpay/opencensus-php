<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Field\Card;
use Constants\Field\Common;
use Constants\Table;

class CreateCards extends Migration {

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CARD, function(Blueprint $table){

            $table->engine = 'InnoDB';

            $table->char(Card::ID, Constants\Fields::ID_LENGTH)
                  ->primary();

            $table->string(Card::NAME);

            $table->string(Card::EXPIRY_MONTH, 2);

            $table->string(Card::EXPIRY_YEAR, 4);

            $table->char(Card::LAST4, 4);

            $table->string(Card::NETWORK);

            $table->string(Card::TYPE, 6);

            $table->string(Card::BANK, 100);

            /**
             * Two letter ISO codes representing the country of the card.
             */
            $table->char(Card::COUNTRY, Constants\Fields::COUNTRY_LENGTH);

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

            $table->boolean('cvv_check')
                  ->nullable();

            $table->boolean('address_line1_check')
                  ->nullable();

            $table->boolean('address_zip_check')
                  ->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer(Common::CREATED_AT);
            $table->integer(Common::UPDATED_AT);
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