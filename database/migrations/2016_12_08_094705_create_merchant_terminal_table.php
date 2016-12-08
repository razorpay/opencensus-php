<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\Terminal;
use RZP\Models\Terminal\Entity as Merchant_Terminal;

class CreateMerchantTerminalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_TERMINAL, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Merchant_Terminal::MERCHANT_ID, Merchant_Terminal::ID_LENGTH);

            $table->char(Merchant_Terminal::TERMINAL_ID, Merchant_Terminal::ID_LENGTH);

            $table->foreign(Merchant_Terminal::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->onDelete('cascade');

            $table->foreign(Merchant_Terminal::TERMINAL_ID)
                  ->references(Terminal\Entity::ID)
                  ->on(Table::TERMINAL)
                  ->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::MERCHANT_TERMINAL, function($table)
        {
            $table->dropForeign(
                Table::MERCHANT_TERMINAL.'_'.MERCHANT_TERMINAL::MERCHANT_ID.'_foreign');

            $table->dropForeign(
                Table::MERCHANT_TERMINAL.'_'.MERCHANT_TERMINAL::TERMINAL_ID.'_foreign');
        });
    }
}
