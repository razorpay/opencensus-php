<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Settlement\Entity as Settlement;
use RZP\Models\FundTransfer\Batch\Entity as BatchFundTransfer;

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

            $table->char(BatchFundTransfer::ID, BatchFundTransfer::ID_LENGTH)
                  ->primary();

            $table->char(BatchFundTransfer::TYPE, 50)
                  ->nullable();

            $table->integer(BatchFundTransfer::DATE);

            $table->string(BatchFundTransfer::CHANNEL, 8);

            $table->bigInteger(BatchFundTransfer::AMOUNT)
                  ->unsigned();

            $table->bigInteger(BatchFundTransfer::PROCESSED_AMOUNT)
                  ->unsigned();

            $table->integer(BatchFundTransfer::FEES);

            $table->integer(BatchFundTransfer::TAX)
                  ->unsigned()
                  ->nullable();

            $table->integer(BatchFundTransfer::API_FEE);

            $table->integer(BatchFundTransfer::GATEWAY_FEE);

            $table->integer(BatchFundTransfer::TOTAL_COUNT);

            $table->integer(BatchFundTransfer::PROCESSED_COUNT);

            $table->integer(BatchFundTransfer::TRANSACTION_COUNT);

            $table->text(BatchFundTransfer::URLS);

            $table->integer(BatchFundTransfer::INITIATED_AT);

            $table->integer(BatchFundTransfer::RECONCILED_AT)
                  ->nullable();

            $table->integer(BatchFundTransfer::RETURNED_AT)
                  ->nullable();

            $table->integer(BatchFundTransfer::CREATED_AT);
            $table->integer(BatchFundTransfer::UPDATED_AT);

            $table->index(BatchFundTransfer::CREATED_AT);
            $table->index(BatchFundTransfer::DATE);
        });

        Schema::table(Table::SETTLEMENT, function($table)
        {
            $table->string(Settlement::BATCH_FUND_TRANSFER_ID)
                  ->nullable();

            $table->foreign(Settlement::BATCH_FUND_TRANSFER_ID)
                  ->references(BatchFundTransfer::ID)
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
