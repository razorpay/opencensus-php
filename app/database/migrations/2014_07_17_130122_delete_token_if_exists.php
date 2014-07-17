<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Token\Entity as Token;
use Models\Merchant;

class DeleteTokenIfExists extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('tokens'))
        {
            Schema::table('transactions', function($table)
            {
                $table->dropForeign('transactions'.'_'.'token_id'.'_foreign');
            });

            Schema::table('tokens', function($table)
            {
                $table->dropForeign(Table::TOKEN.'_'.Token::CARD_ID.'_foreign');

                $table->dropForeign(Table::TOKEN.'_'.Token::MERCHANT_ID.'_foreign');
            });

            Schema::drop(Table::TOKEN);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create(Table::TOKEN, function($table){
            $table->engine = 'InnoDB';

            $table->char(Token::ID, Token::ID_LENGTH)
                  ->primary();

            $table->char(Token::CARD_ID, Token::ID_LENGTH)
                  ->unique()
                  ->nullable();

            $table->integer(Token::MERCHANT_ID)
                  ->unsigned()
                  ->nullable();

            $table->foreign(Token::CARD_ID)
                  ->references(Token::ID)
                  ->on(Table::CARD)
                  ->on_delete('SET NULL');

            $table->foreign(Token::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('SET NULL');
        });

        Schema::table('transactions', function($table)
        {
            $table->foreign('token_id')
                  ->references(Token::ID)
                  ->on(Table::TOKEN);
        });
    }
}
