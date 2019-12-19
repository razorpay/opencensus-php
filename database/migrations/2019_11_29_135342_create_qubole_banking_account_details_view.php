<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\BankingAccount\Detail\Entity as BankingAccountDetails;

class CreateQuboleBankingAccountDetailsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            BankingAccountDetails::ID,
            BankingAccountDetails::BANKING_ACCOUNT_ID,
            BankingAccountDetails::MERCHANT_ID,
            BankingAccountDetails::CREATED_AT,
            BankingAccountDetails::UPDATED_AT,
            BankingAccountDetails::DELETED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_banking_account_details_view AS
                        SELECT ' . $columnStr .
            ' FROM ' . Table::BANKING_ACCOUNT_DETAIL;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_banking_account_details_view');
    }
}
