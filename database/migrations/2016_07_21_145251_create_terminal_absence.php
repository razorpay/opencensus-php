<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Terminal;
use RZP\Models\Terminal\Absence\Entity as DowntimeTrace;

class CreateTerminalAbsence extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TERMINAL_ABSENCE, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments(DowntimeTrace::ID);

            $table->string(DowntimeTrace::GATEWAY);

            $table->dateTime(DowntimeTrace::DOWNTIME_FROM);

            $table->dateTime(DowntimeTrace::DOWNTIME_TO);

            $table->integer(DowntimeTrace::CREATED_AT);

            $table->integer(DowntimeTrace::UPDATED_AT);

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
        Schema::drop(Table::TERMINAL_ABSENCE);
    }
}
