<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Schedule\Entity as Schedule;
use RZP\Models\Merchant\Entity as Merchant;
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

            $table->string(Schedule::NAME, 50)
                  ->nullable();

            $table->char(Schedule::MERCHANT_ID, Schedule::ID_LENGTH);

            $table->string(Schedule::TYPE, 15);

            $table->string(Schedule::PERIOD, 15);

            $table->tinyInteger(Schedule::INTERVAL)
                  ->nullable();

            $table->tinyInteger(Schedule::ANCHOR)
                  ->nullable();

            $table->tinyInteger(Schedule::DELAY);

            $table->integer(Schedule::NEXT_RUN)
                  ->nullable();

            $table->integer(Schedule::CREATED_AT);
            $table->integer(Schedule::UPDATED_AT);

            $table->index(Schedule::TYPE);
            $table->index(Schedule::CREATED_AT);
            $table->index(Schedule::NEXT_RUN);

            $table->foreign(Schedule::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });

        Schema::table(Table::MERCHANT, function($table)
        {
            $table->char(Merchant::SETTLEMENT_SCHEDULE_ID, Merchant::ID_LENGTH)
                  ->nullable()
                  ->after(Merchant::SETTLEMENT_SCHEDULE);

            $table->foreign(Merchant::SETTLEMENT_SCHEDULE_ID)
                  ->references(Schedule::ID)
                  ->on(Table::SCHEDULE)
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
        Schema::table(Table::MERCHANT, function($table)
        {
            $table->dropForeign(Table::MERCHANT . '_' . Merchant::SETTLEMENT_SCHEDULE_ID . '_foreign');

            $table->dropColumn(Merchant::SETTLEMENT_SCHEDULE_ID);
        });

        Schema::table(Table::SCHEDULE, function($table)
        {
            $table->dropForeign(Table::SCHEDULE.'_'.Schedule::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::SCHEDULE);
    }
}
