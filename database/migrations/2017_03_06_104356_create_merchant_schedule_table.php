<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Schedule\Entity as Terminal;

class CreateMerchantSchedule extends Migration
{
    const MERCHANT_ID = 'merchant_id';
    const SCHEDULE_ID = 'schedule_id';
    const METHOD      = 'method';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_SCHEDULE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(self::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->char(self::METHOD, 20);

            $table->char(self::SCHEDULE_ID, Schedule::ID_LENGTH);

            $table->foreign(self::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->onDelete('cascade');

            $table->foreign(self::TERMINAL_ID)
                  ->references(Terminal::ID)
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
        Schema::table(Table::MERCHANT_SCHEDULE, function($table)
        {
            $table->dropForeign(
                Table::MERCHANT_SCHEDULE.'_'.self::MERCHANT_ID.'_foreign');

            $table->dropForeign(
                Table::MERCHANT_SCHEDULE.'_'.self::SCHEDULE_ID.'_foreign');
        });

        Schema::drop(Table::MERCHANT_SCHEDULE);
    }
}
