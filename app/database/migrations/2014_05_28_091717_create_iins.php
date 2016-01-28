<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Card\IIN;

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
            $table->integer(IIN\Entity::IIN)->primary();

            $table->string(IIN\Entity::CATEGORY)
                  ->nullable();

            $table->string(IIN\Entity::NETWORK)
                  ->nullable();

            $table->string(IIN\Entity::TYPE)
                  ->nullable();

            $table->char(IIN\Entity::COUNTRY, IIN\Entity::COUNTRY_LENGTH)
                  ->nullable();

            $table->string(IIN\Entity::ISSUER)
                  ->nullable();

            $table->string(IIN\Entity::ISSUER_NAME)
                  ->nullable();

            $table->boolean(IIN\Entity::EMI)
                  ->default(0);

            $table->string(IIN\Entity::TRIVIA)
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
