<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Customer\Token\Entity as Token;
use RZP\Models\Customer\Token\Constants as Constants;

class UpdateTokens extends Migration
{

    public function up()
    {
        Schema::table(Table::TOKEN, function($table)
        {
            $table->engine = 'InnoDB';

            $table->enum(Token::SOURCE, [Constants::MERCHANT, Constants::ISSUER])
                ->nullable(false)
                ->default('merchant');
        });
    }


    public function down()
    {
        Schema::table(Table::TOKEN, function($table)
        {
            $table->dropColumn(Token::SOURCE);
        });
    }
}
