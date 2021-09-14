<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Card\IIN;
use RZP\Models\Card;

class CreateIins extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::IIN, function(Blueprint $table)
        {
            $table->char(IIN\Entity::IIN, 6)->primary();

            $table->string(IIN\Entity::CATEGORY)
                  ->nullable();

            $table->string(IIN\Entity::NETWORK)
                  ->nullable();

            $table->string(IIN\Entity::TYPE)
                  ->nullable();

            $table->string(IIN\Entity::SUBTYPE)
                  ->default(Card\SubType::CONSUMER);

           $table->string(IIN\Entity::PRODUCT_CODE)
                  ->nullable();

            $table->string(IIN\Entity::MESSAGE_TYPE)
                  ->nullable();

            $table->char(IIN\Entity::COUNTRY, IIN\Entity::COUNTRY_LENGTH)
                  ->nullable();

            $table->string(IIN\Entity::ISSUER)
                  ->nullable();

            $table->string(IIN\Entity::ISSUER_NAME)
                  ->nullable();

            $table->string(IIN\Entity::COBRANDING_PARTNER, 20)
                ->nullable();

            $table->tinyInteger(IIN\Entity::EMI)
                  ->default(0);

            $table->integer(IIN\Entity::CREATED_AT);

            $table->integer(IIN\Entity::UPDATED_AT);

            $table->tinyInteger(IIN\Entity::OTP_READ)
                  ->default(0);

            $table->integer(IIN\Entity::FLOWS)
                  ->unsigned()
                  ->default(1);

            $table->string(IIN\Entity::TRIVIA)
                  ->nullable();

            $table->tinyInteger(IIN\Entity::ENABLED)
                  ->default(1);

            $table->tinyInteger(IIN\Entity::LOCKED)
                  ->default(0);

            $table->tinyInteger(IIN\Entity::RECURRING)
                  ->default(0);

            $table->integer(IIN\Entity::MANDATE_HUBS)
                ->unsigned()
                ->default(0);

            $table->index(IIN\Entity::OTP_READ);
            $table->index(IIN\Entity::EMI);
            $table->index(IIN\Entity::COBRANDING_PARTNER);
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
