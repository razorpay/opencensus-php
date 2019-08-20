<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\BankAccount\Entity as BankAccount;

class CreateQuboleBankAccountsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            BankAccount::ID,
            BankAccount::MERCHANT_ID,
            BankAccount::ENTITY_ID,
            BankAccount::TYPE,
            BankAccount::BENEFICIARY_CODE,
            BankAccount::IFSC_CODE,
            BankAccount::ACCOUNT_TYPE,
            BankAccount::BENEFICIARY_NAME,
            BankAccount::REGISTERED_BENEFICIARY_NAME,
            BankAccount::BENEFICIARY_ADDRESS4,
            BankAccount::MOBILE_BANKING_ENABLED,
            BankAccount::MPIN,
            BankAccount::BENEFICIARY_CITY,
            BankAccount::BENEFICIARY_STATE,
            BankAccount::BENEFICIARY_COUNTRY,
            BankAccount::BENEFICIARY_PIN,
            BankAccount::FTS_FUND_ACCOUNT_ID,
            '`virtual`',
            BankAccount::CREATED_AT,
            BankAccount::UPDATED_AT,
            BankAccount::DELETED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_bank_accounts_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::BANK_ACCOUNT;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_bank_accounts_view');
    }
}
