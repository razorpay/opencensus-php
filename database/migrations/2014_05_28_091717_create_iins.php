<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Card\IIN;

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

            $table->tinyInteger(IIN\Entity::EMI)
                  ->default(0);

            $table->integer(IIN\Entity::CREATED_AT);

            $table->integer(IIN\Entity::UPDATED_AT);

            $table->tinyInteger(IIN\Entity::OTP_READ)
                  ->default(0);

            $table->string(IIN\Entity::TRIVIA)
                  ->nullable();

            $table->index(IIN\Entity::OTP_READ);
            $table->index(IIN\Entity::EMI);
            $table->index(IIN\Entity::CREATED_AT);
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
