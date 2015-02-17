<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Settlement\Daily\Entity as DailySettlement;

class CreateDailySettlements extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::DAILY_SETTLEMENT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(DailySettlement::ID, DailySettlement::ID_LENGTH)
                  ->primary();

            $table->integer(DailySettlement::DATE);

            $table->string(DailySettlement::CHANNEL, 8);

            $table->integer(DailySettlement::AMOUNT);

            $table->text(DailySettlement::URLS);

            $table->integer(DailySettlement::INITIATED_AT);

            $table->integer(DailySettlement::RECONCILED_AT)
                  ->nullable();

            $table->integer(DailySettlement::RETURNED_AT)
                  ->nullable();

            $table->integer(DailySettlement::CREATED_AT);
            $table->integer(DailySettlement::UPDATED_AT);

            $table->index(DailySettlement::CREATED_AT);
            $table->index(DailySettlement::DATE);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::DAILY_SETTLEMENT);
    }
}
