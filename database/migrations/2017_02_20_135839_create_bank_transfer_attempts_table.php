<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\BankTransferAttempt\Entity as BankTransferAttempt;

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
            $table->char(BankTransferAttempt::ID, BankTransferAttempt::ID_LENGTH)
                  ->primary();

            $table->string(BankTransferAttempt::ENTITY_TYPE);

            $table->string(BankTransferAttempt::ENTITY_ID);

            $table->char(BankTransferAttempt::STATUS, 1)
                  ->nullable();

            $table->string(BankTransferAttempt::UTR, 20)
                  ->nullable()
                  ->unique();

            $table->string(BankTransferAttempt::REMARKS, 30)
                  ->nullable();

            $table->string(BankTransferAttempt::DATE_TIME, 16)
                  ->nullable();

            $table->string(BankTransferAttempt::CMS_REF_NO, 16)
                  ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::BANK_TRANSFER_ATTEMPT);
    }
}
