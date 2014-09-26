<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Card;

class CreateIinsTable extends Migration {

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

            $table->string(Card\Detail::CATEGORY, 26);

            $table->string(Card\Detail::BRAND, 10);

            $table->string(Card\Detail::TYPE, 6);

            $table->char(Card\Detail::COUNTRY, Card\Entity::COUNTRY_LENGTH);

            $table->string(Card\Detail::ISSUER, 100);
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
