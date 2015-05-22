<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Merchant;
use Models\Terminal\Entity as Terminal;

class CreateTerminals extends Migration
{

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

            $table->char(Terminal::ID, Terminal::ID_LENGTH)
                  ->primary();

            $table->char(Terminal::MERCHANT_ID, Terminal::ID_LENGTH);

            $table->integer(Terminal::USED_COUNT)
                  ->unsigned()
                  ->default(0);

            $table->string(Terminal::GATEWAY);

            $table->string(Terminal::GATEWAY_MERCHANT_ID);

            $table->string(Terminal::GATEWAY_TERMINAL_ID);

            $table->text(Terminal::GATEWAY_TERMINAL_PASSWORD);

            $table->string(Terminal::GATEWAY_ACCESS_CODE)->nullable();

            $table->string(Terminal::GATEWAY_SECURE_SECRET)->nullable();

            $table->boolean(Terminal::CARD)
                  ->default(0);

            $table->boolean(Terminal::NETBANKING)
                  ->default(0);

            $table->boolean(Terminal::SHARED)
                  ->default(0);

            $table->integer(Terminal::CREATED_AT);

            $table->integer(Terminal::UPDATED_AT);

            $table->integer(Terminal::DELETED_AT)
                  ->unsigned()
                  ->nullable();

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
