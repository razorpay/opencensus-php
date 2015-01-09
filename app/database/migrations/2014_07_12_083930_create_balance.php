<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Merchant;
use Models\Merchant\Balance;

class CreateBalance extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BALANCE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Balance::ID, Balance::ID_LENGTH)
                  ->primary();

            $table->integer(Balance::BALANCE)
                  ->default(0);

            $table->integer(Balance::ON_HOLD)
                  ->default(0);

            $table->integer(Balance::CREATED_AT);
            $table->integer(Balance::UPDATED_AT);

            $table->foreign(Balance::ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::BALANCE, function($table)
        {
            $table->dropForeign(Table::BALANCE.'_'.Balance::ID.'_foreign');
        });

        Schema::drop(Table::BALANCE);
    }
}
