<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Balance\Entity as Balance;

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

            $table->bigInteger(Balance::BALANCE)
                  ->default(0);

            $table->bigInteger(Balance::ON_HOLD)
                  ->default(0);

            $table->bigInteger(Balance::CREDITS)
                  ->default(0);

            $table->bigInteger(Balance::FEE_CREDITS)
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
