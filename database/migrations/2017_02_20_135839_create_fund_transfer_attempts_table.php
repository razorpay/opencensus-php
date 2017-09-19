<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\FundTransfer\Attempt\Entity as FundTransferAttempt;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Models\FundTransfer\Batch\Entity as BatchFundTransfer;
use RZP\Models\Merchant\Entity as Merchant;

class CreateFundTransferAttemptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::FUND_TRANSFER_ATTEMPT, function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->char(FundTransferAttempt::ID, FundTransferAttempt::ID_LENGTH)
                  ->primary();

            $table->string(FundTransferAttempt::SOURCE_TYPE, 20);

            $table->string(FundTransferAttempt::SOURCE_ID, FundTransferAttempt::ID_LENGTH);

            $table->string(FundTransferAttempt::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->char(FundTransferAttempt::BANK_ACCOUNT_ID, BankAccount::ID_LENGTH)
                  ->nullable();

            $table->string(FundTransferAttempt::CHANNEL, 8);

            $table->string(FundTransferAttempt::VERSION, 3);

            $table->string(FundTransferAttempt::BANK_STATUS_CODE, 4)
                  ->nullable();

            $table->string(FundTransferAttempt::STATUS);

            $table->string(FundTransferAttempt::UTR)
                  ->nullable()
                  ->unique();

            $table->string(FundTransferAttempt::NARRATION)
                  ->nullable();

            $table->string(FundTransferAttempt::REMARKS)
                  ->nullable();

            $table->string(FundTransferAttempt::FAILURE_REASON)
                  ->nullable();

            $table->string(FundTransferAttempt::DATE_TIME)
                  ->nullable();

            $table->string(FundTransferAttempt::CMS_REF_NO)
                  ->nullable();

            $table->string(FundTransferAttempt::BATCH_FUND_TRANSFER_ID, BatchFundTransfer::ID_LENGTH)
                  ->nullable();

            $table->integer(FundTransferAttempt::CREATED_AT);

            $table->integer(FundTransferAttempt::UPDATED_AT);

            $table->index(FundTransferAttempt::STATUS);

            $table->index([FundTransferAttempt::SOURCE_ID, FundTransferAttempt::SOURCE_TYPE]);

            $table->index(FundTransferAttempt::CHANNEL);

            $table->index(FundTransferAttempt::CREATED_AT);

            $table->foreign(FundTransferAttempt::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(FundTransferAttempt::BATCH_FUND_TRANSFER_ID)
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
        Schema::table(Table::FUND_TRANSFER_ATTEMPT, function($table)
        {
            $table->dropForeign(
                Table::FUND_TRANSFER_ATTEMPT.'_'.FundTransferAttempt::BATCH_FUND_TRANSFER_ID.'_foreign');

            $table->dropForeign(
                Table::FUND_TRANSFER_ATTEMPT.'_'.FundTransferAttempt::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::FUND_TRANSFER_ATTEMPT);
    }
}
