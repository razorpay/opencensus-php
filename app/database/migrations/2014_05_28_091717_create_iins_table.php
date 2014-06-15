<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Field\IIN;
use Constants\Field\Common;
use Constants\Table;

class CreateIinsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::IINS, function(Blueprint $table)
        {
            $table->integer(IIN::IIN)->primary();

            $table->string(IIN::CATEGORY, 26);

            $table->string(IIN::BRAND, 10);

            $table->string(IIN::TYPE, 6);

            $table->char(IIN::COUNTRY, Constants\Fields::COUNTRY_LENGTH);

            $table->string(IIN::BANK, 100);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::IINS);
    }

}
