<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Field\Common;
use Constants\Field\Token;
use Constants\Table;

class CreateTokens extends Migration {

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TOKEN, function(Blueprint $table){
            $table->engine = 'InnoDB';

            $table->char(Token::ID, Constants\Fields::ID_LENGTH)
                  ->primary();

            $table->integer(Token::CARD_ID)
                  ->unsigned()
                  ->nullable();

            $table->integer(Common::MERCHANT_ID)
                  ->unsigned()
                  ->nullable();

            $table->boolean(TOKEN::EXPIRED);

            // Adds created_at and updated_at columns to the table
            $table->integer(Common::CREATED_AT);
            $table->integer(Common::UPDATED_AT);

            $table->foreign(Token::CARD_ID)
                  ->references(Common::ID)
                  ->on(Table::CARD)
                  ->on_delete('SET NULL');

            $table->foreign(Common::MERCHANT_ID)
                  ->references(\Constants\Field\Merchant::ID)
                  ->on(Table::MERCHANT);
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::TOKEN, function($table){

            $table->dropForeign(Table::TOKEN.'_card_id_foreign');

            $table->dropForeign(Table::TOKEN.'_merchant_id_foreign');
        });

        Schema::drop(Table::TOKEN);
    }

}