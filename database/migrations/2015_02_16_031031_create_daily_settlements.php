<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Settlement\Daily\Entity as DailySettlement;

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

            $table->bigInteger(DailySettlement::AMOUNT)
                  ->unsigned();

            $table->integer(DailySettlement::FEES);

            $table->integer(DailySettlement::SERVICE_TAX)
                  ->unsigned()
                  ->nullable();

            $table->integer(DailySettlement::API_FEE);

            $table->integer(DailySettlement::GATEWAY_FEE);

            $table->integer(DailySettlement::SETTLEMENT_COUNT);

            $table->integer(DailySettlement::TRANSACTION_COUNT);

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
