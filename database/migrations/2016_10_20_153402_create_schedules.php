<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Schedule\Entity as Schedule;
use RZP\Constants\Table;

class CreateSchedules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SCHEDULE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Schedule::ID, Schedule::ID_LENGTH)
                  ->primary();

            $table->string(Schedule::NAME);

            $table->char(Schedule::OWNER_ID, Schedule::ID_LENGTH);

            $table->string(Schedule::TYPE);

            $table->string(Schedule::PERIOD);

            $table->integer(Schedule::INTERVAL)
                  ->nullable();

            $table->integer(Schedule::ANCHOR)
                  ->nullable();

            $table->integer(Schedule::DELAY);

            $table->integer(Schedule::NEXT_RUN)
                  ->nullable();

            $table->integer(Schedule::CREATED_AT);
            $table->integer(Schedule::UPDATED_AT);

            $table->index(Schedule::OWNER_ID);
            $table->index(Schedule::TYPE);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::SCHEDULE);
    }
}
