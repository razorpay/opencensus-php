<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Payout\BankingAccount\Entity as BankingAccount;


class CreatePsBankingAccounts extends Migration
{
    /**
     * This table doesn't exist on prod. It only exists on CI.
     * This is only to run test cases related to data migration of Payouts.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ps_banking_accounts', function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(BankingAccount::ID, 14)->primary();

            $table->char(BankingAccount::MERCHANT_ID, 14);

            $table->char(BankingAccount::BALANCE_ID, 14)
                ->nullable();

            $table->string(BankingAccount::CHANNEL, 255)
                ->nullable();

            $table->string(BankingAccount::STATUS, 255);

            $table->char(BankingAccount::ACCOUNT_NUMBER, 40)
                ->nullable();

            $table->string(BankingAccount::ACCOUNT_TYPE, 255);

            $table->char(BankingAccount::FTS_FUND_ACCOUNT_ID, 14)
                ->nullable();

            $table->tinyInteger(BankingAccount::PAYOUT_SERVICE_ENABLED)
                ->default(0);

            $table->integer(BankingAccount::CREATED_AT);

            $table->integer(BankingAccount::UPDATED_AT);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ps_banking_accounts');
    }
}
