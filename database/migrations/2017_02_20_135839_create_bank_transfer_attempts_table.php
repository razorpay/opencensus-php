<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\BankTransferAttempt\Entity as BankTransferAttempt;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Models\Settlement\Batch\Entity as BatchTransfer;

class CreateBankTransferAttemptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BANK_TRANSFER_ATTEMPT, function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->char(BankTransferAttempt::ID, BankTransferAttempt::ID_LENGTH)
                  ->primary();

            $table->string(BankTransferAttempt::ENTITY_TYPE, 20);

            $table->string(BankTransferAttempt::ENTITY_ID, BankTransferAttempt::ID_LENGTH);

            $table->char(BankTransferAttempt::BANK_ACCOUNT_ID, BankAccount::ID_LENGTH)
                  ->nullable();

            $table->string(BankTransferAttempt::CHANNEL, 8);

            $table->string(BankTransferAttempt::VERSION, 3);

            $table->char(BankTransferAttempt::BANK_STATUS_CODE)
                  ->nullable();

            $table->string(BankTransferAttempt::STATUS);

            $table->string(BankTransferAttempt::UTR)
                  ->nullable()
                  ->unique();

            $table->string(BankTransferAttempt::REMARKS)
                  ->nullable();

            $table->string(BankTransferAttempt::FAILURE_REASON)
                  ->nullable();

            $table->string(BankTransferAttempt::DATE_TIME)
                  ->nullable();

            $table->string(BankTransferAttempt::CMS_REF_NO)
                  ->nullable();

            $table->string(BankTransferAttempt::BATCH_TRANSFER_ID, BatchTransfer::ID_LENGTH)
                  ->nullable();

            $table->integer(BankTransferAttempt::CREATED_AT);

            $table->integer(BankTransferAttempt::UPDATED_AT);

            $table->index(BankTransferAttempt::STATUS);

            $table->index([BankTransferAttempt::ENTITY_ID, BankTransferAttempt::ENTITY_TYPE]);

            $table->index(BankTransferAttempt::CHANNEL);

            $table->index(BankTransferAttempt::CREATED_AT);

            $table->foreign(BankTransferAttempt::BATCH_TRANSFER_ID)
                  ->references(BatchTransfer::ID)
                  ->on(Table::BATCH_SETTLEMENT)
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
        Schema::table(Table::BANK_TRANSFER_ATTEMPT, function($table)
        {
            $table->dropForeign(
                Table::BANK_TRANSFER_ATTEMPT.'_'.BankTransferAttempt::BATCH_SETTLEMENT.'_foreign');
        });

        Schema::drop(Table::BANK_TRANSFER_ATTEMPT);
    }
}
