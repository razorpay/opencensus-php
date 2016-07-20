<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Terminal;
use RZP\Models\Terminal\DowntimeTrace\Entity as DowntimeTrace;

class CreateTerminalDowntimeTraces extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TERMINAL_DOWNTIME_SCHEDULE, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(DowntimeTrace::ID, DowntimeTrace::ID_LENGTH)
                ->primary();

            $table->char(DowntimeTrace::TERMINAL_ID, DowntimeTrace::ID_LENGTH);

            $table->string(DowntimeTrace::GATEWAY);

            $table->dateTime(DowntimeTrace::DOWNTIME_FROM);

            $table->date(DowntimeTrace::DOWNTIME_TO);

            $table->integer(DowntimeTrace::CREATED_AT);

            $table->integer(DowntimeTrace::UPDATED_AT);

            $table->foreign(DowntimeTrace::TERMINAL_ID)
                ->references(Terminal\Entity::ID)
                ->on(Table::TERMINAL)
                ->on_delete('restrict');

            $table->index(DowntimeTrace::TERMINAL_ID);

            $table->index(DowntimeTrace::GATEWAY);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::TERMINAL_DOWNTIME_SCHEDULE, function($table)
        {
            $table->dropForeign(
                TABLE::TERMINAL_DOWNTIME_SCHEDULE.'_'.DowntimeTrace::TERMINAL_ID.'_foreign');

        });

        Schema::drop(Table::TERMINAL_DOWNTIME_SCHEDULE);
    }
}
