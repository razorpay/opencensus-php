<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Schedule\Entity as MerchantSchedule;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Schedule\Entity as Schedule;

class CreateMerchantSchedules extends Migration
{
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

            $table->increments(MerchantSchedule::ID);

            $table->char(MerchantSchedule::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->char(MerchantSchedule::METHOD, 20)
                  ->nullable();

            $table->char(MerchantSchedule::SCHEDULE_ID, Schedule::ID_LENGTH);

            $table->integer(MerchantSchedule::CREATED_AT);

            $table->integer(MerchantSchedule::UPDATED_AT);

            $table->foreign(MerchantSchedule::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->onDelete('cascade');

            $table->foreign(MerchantSchedule::SCHEDULE_ID)
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
        Schema::table(Table::MERCHANT_SCHEDULE, function($table)
        {
            $table->dropForeign(
                Table::MERCHANT_SCHEDULE.'_'.MerchantSchedule::MERCHANT_ID.'_foreign');

            $table->dropForeign(
                Table::MERCHANT_SCHEDULE.'_'.MerchantSchedule::SCHEDULE_ID.'_foreign');
        });

        Schema::drop(Table::MERCHANT_SCHEDULE);
    }
}
