<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\BankingAccount\Entity as BankingAccount;

class CreateQuboleBankingAccountsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            BankingAccount::ID,
            BankingAccount::MERCHANT_ID,
            BankingAccount::ACCOUNT_IFSC,
            BankingAccount::STATUS,
            BankingAccount::BENEFICIARY_PIN,
            BankingAccount::BANK_INTERNAL_STATUS,
            BankingAccount::CHANNEL,
            BankingAccount::PINCODE,
            BankingAccount::FTS_FUND_ACCOUNT_ID,
            BankingAccount::BALANCE_ID,
            BankingAccount::BANK_INTERNAL_REFERENCE_NUMBER,
            BankingAccount::BENEFICIARY_NAME,
            BankingAccount::ACCOUNT_CURRENCY,
            BankingAccount::BENEFICIARY_CITY,
            BankingAccount::BENEFICIARY_STATE,
            BankingAccount::BENEFICIARY_COUNTRY,
            BankingAccount::BANK_REFERENCE_NUMBER,
            BankingAccount::ACCOUNT_ACTIVATION_DATE,
            BankingAccount::REFERENCE1,
            BankingAccount::ACCOUNT_TYPE,
            BankingAccount::CREATED_AT,
            BankingAccount::UPDATED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_banking_accounts_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::BANKING_ACCOUNT;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_banking_accounts_view');
    }
}
