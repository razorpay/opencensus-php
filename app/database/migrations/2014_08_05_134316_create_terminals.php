<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Merchant;
use Models\Terminal\Entity as Terminal;

class CreateTerminals extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TERMINAL, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Terminal::ID, 24)
                  ->primary();

            $table->char(Terminal::MERCHANT_ID, 24);

            $table->string(Terminal::GATEWAY);

            $table->string(Terminal::GATEWAY_TERMINAL_ID);

            $table->string(Terminal::GATEWAY_TERMINAL_PASSWORD);

            $table->foreign(Terminal::MERCHANT_ID)
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
        Schema::table(Table::TERMINAL, function($table)
        {
            $table->dropForeign(
                TABLE::TERMINAL.'_'.Terminal::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::TERMINAL);
    }
}
