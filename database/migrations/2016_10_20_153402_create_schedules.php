<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Schedule\Entity as Schedule;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Admin\Org\Entity as Org;
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

            $table->char(Schedule::ORG_ID, Org::ID_LENGTH)
                ->nullable();

            $table->char(Schedule::MERCHANT_ID, Merchant::ID_LENGTH)
                  ->nullable();

            $table->string(Schedule::TYPE, 255)
                  ->nullable();

            $table->string(Schedule::PERIOD, 15);

            $table->integer(Schedule::INTERVAL)
                  ->nullable();

            $table->integer(Schedule::ANCHOR)
                  ->nullable();

            $table->tinyInteger(Schedule::HOUR)
                  ->default(0);

            $table->tinyInteger(Schedule::DELAY)
                  ->default(0);

            $table->integer(Schedule::CREATED_AT);
            $table->integer(Schedule::UPDATED_AT);

            $table->integer(Schedule::DELETED_AT)
                  ->unsigned()
                  ->nullable();

            $table->index(Schedule::NAME);
            $table->index(Schedule::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::SCHEDULE, function($table)
        {
            $table->dropForeign(Table::SCHEDULE.'_'.Schedule::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::SCHEDULE);
    }
}
