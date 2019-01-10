<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Schedule\Task\Entity as ScheduleTask;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Schedule\Entity as Schedule;

class CreateScheduleTasks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SCHEDULE_TASK, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(ScheduleTask::ID);

            $table->char(ScheduleTask::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->char(ScheduleTask::ENTITY_ID, ScheduleTask::ID_LENGTH);

            $table->char(ScheduleTask::ENTITY_TYPE, 20);

            $table->char(ScheduleTask::TYPE, 20);

            $table->char(ScheduleTask::METHOD, 20)
                  ->nullable();

            $table->boolean(ScheduleTask::INTERNATIONAL)
                  ->default(0);

            $table->char(ScheduleTask::SCHEDULE_ID, Schedule::ID_LENGTH);

            $table->integer(ScheduleTask::NEXT_RUN_AT);

            $table->integer(ScheduleTask::LAST_RUN_AT)
                  ->nullable();

            $table->integer(ScheduleTask::CREATED_AT);

            $table->integer(ScheduleTask::UPDATED_AT);

            $table->integer(ScheduleTask::DELETED_AT)
                  ->nullable();

            $table->index(ScheduleTask::ENTITY_ID);
            $table->index(ScheduleTask::NEXT_RUN_AT);
            $table->index(ScheduleTask::LAST_RUN_AT);
            $table->index(ScheduleTask::CREATED_AT);

            $table->foreign(ScheduleTask::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->onDelete('cascade');

            $table->foreign(ScheduleTask::SCHEDULE_ID)
                  ->references(Schedule::ID)
                  ->on(Table::SCHEDULE)
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
        Schema::table(Table::SCHEDULE_TASK, function($table)
        {
            $table->dropForeign(
                Table::SCHEDULE_TASK . '_' . ScheduleTask::MERCHANT_ID . '_foreign');

            $table->dropForeign(
                Table::SCHEDULE_TASK . '_' . ScheduleTask::SCHEDULE_ID . '_foreign');
        });

        Schema::drop(Table::SCHEDULE_TASK);
    }
}
