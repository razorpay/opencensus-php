<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Card;

class CreateIins extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::IIN, function(Blueprint $table)
        {
            $table->integer(Card\Detail::IIN)->primary();

            $table->string(Card\Detail::CATEGORY)
                  ->nullable();

            $table->string(Card\Detail::NETWORK)
                  ->nullable();

            $table->string(Card\Detail::TYPE)
                  ->nullable();

            $table->char(Card\Detail::COUNTRY, Card\Entity::COUNTRY_LENGTH)
                  ->nullable();

            $table->string(Card\Detail::ISSUER)
                  ->nullable();

            $table->string(Card\Detail::TRIVIA)
                  ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::IIN);
    }

}
