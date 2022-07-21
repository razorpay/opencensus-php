<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use RZP\Constants\Table;
use RZP\Models\Card\TokenisedIIN;

class CreateTokenisedIins extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TOKENISED_IIN, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(TokenisedIIN\Entity::ID);

            $table->char(TokenisedIIN\Entity::HIGH_RANGE, 9);

            $table->char(TokenisedIIN\Entity::LOW_RANGE, 9);

            $table->char(TokenisedIIN\Entity::IIN, 9);

            $table->integer(TokenisedIIN\Entity::CREATED_AT);

            $table->integer(TokenisedIIN\Entity::UPDATED_AT);

            $table->integer(TokenisedIIN\Entity::TOKEN_IIN_LENGTH)->default(9);

            $table->index(TokenisedIIN\Entity::IIN);
            $table->index([TokenisedIIN\Entity::HIGH_RANGE, TokenisedIIN\Entity::LOW_RANGE]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::TOKENISED_IIN);
    }

}
