<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Merchant\Schedule\Entity as MerchantSchedule;
use RZP\Constants\Table;

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

            $table->char(MerchantSchedule::ID, MerchantSchedule::ID_LENGTH)
                  ->primary();

            $table->char(MerchantSchedule::MERCHANT_ID, MerchantSchedule::ID_LENGTH);

            $table->char(MerchantSchedule::SCHEDULE_ID, MerchantSchedule::ID_LENGTH);

            $table->integer(MerchantSchedule::LAST_RUN)
                  ->nullable();

            $table->integer(MerchantSchedule::CREATED_AT);
            $table->integer(MerchantSchedule::UPDATED_AT);

            $table->index(MerchantSchedule::MERCHANT_ID);
            $table->index(MerchantSchedule::SCHEDULE_ID);
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
