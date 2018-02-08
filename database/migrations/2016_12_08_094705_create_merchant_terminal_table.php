<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Terminal\Entity as Terminal;

class CreateMerchantTerminalTable extends Migration
{
    const MERCHANT_ID = 'merchant_id';
    const TERMINAL_ID = 'terminal_id';
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

            $table->char(self::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->char(self::TERMINAL_ID, Terminal::ID_LENGTH);

            $table->foreign(self::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->onDelete('cascade');

            $table->foreign(self::TERMINAL_ID)
                  ->references(Terminal::ID)
                  ->on(Table::TERMINAL)
                  ->onDelete('cascade');

            $table->unique([self::MERCHANT_ID, self::TERMINAL_ID]);
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
                Table::MERCHANT_TERMINAL.'_'.self::MERCHANT_ID.'_foreign');

            $table->dropForeign(
                Table::MERCHANT_TERMINAL.'_'.self::TERMINAL_ID.'_foreign');
        });

        Schema::drop(Table::MERCHANT_TERMINAL);
    }
}
