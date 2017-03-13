<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Settlement\Entity as Settlement;
use RZP\Models\FundTransfer\Batch\Entity as BatchSettlement;

class CreateDailySettlements extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BATCH_FUND_TRANSFER, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(BatchSettlement::ID, BatchSettlement::ID_LENGTH)
                  ->primary();

            $table->char(BatchSettlement::TYPE, 50)
                  ->nullable();

            $table->integer(BatchSettlement::DATE);

            $table->string(BatchSettlement::CHANNEL, 8);

            $table->bigInteger(BatchSettlement::AMOUNT)
                  ->unsigned();

            $table->integer(BatchSettlement::FEES);

            $table->integer(BatchSettlement::SERVICE_TAX)
                  ->unsigned()
                  ->nullable();

            $table->integer(BatchSettlement::API_FEE);

            $table->integer(BatchSettlement::GATEWAY_FEE);

            $table->integer(BatchSettlement::TOTAL_COUNT);

            $table->integer(BatchSettlement::TRANSACTION_COUNT);

            $table->text(BatchSettlement::URLS);

            $table->integer(BatchSettlement::INITIATED_AT);

            $table->integer(BatchSettlement::RECONCILED_AT)
                  ->nullable();

            $table->integer(BatchSettlement::RETURNED_AT)
                  ->nullable();

            $table->integer(BatchSettlement::CREATED_AT);
            $table->integer(BatchSettlement::UPDATED_AT);

            $table->index(BatchSettlement::CREATED_AT);
            $table->index(BatchSettlement::DATE);
        });

        Schema::table(Table::SETTLEMENT, function($table)
        {
            $table->string(Settlement::BATCH_FUND_TRANSFER_ID)
                  ->nullable();

            $table->foreign(Settlement::BATCH_FUND_TRANSFER_ID)
                  ->references(BatchSettlement::ID)
                  ->on(Table::BATCH_FUND_TRANSFER)
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
        Schema::table(Table::SETTLEMENT, function($table)
        {
            $table->dropForeign(
                Table::SETTLEMENT . '_' . SETTLEMENT::BATCH_FUND_TRANSFER_ID . '_foreign');
        });

        Schema::drop(Table::BATCH_FUND_TRANSFER);
    }
}
