<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedBankingAccountsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            '"*redacted*" AS beneficiary_pin',
            'account_currency',
            '"*redacted*" AS beneficiary_address3',
            'created_at',
            'bank_internal_status',
            '"*redacted*" AS beneficiary_email',
            'bank_reference_number',
            'updated_at',
            'channel',
            '"*redacted*" AS beneficiary_mobile',
            'account_activation_date',
            'last_statement_attempt_at',
            'id',
            '"*redacted*" AS pincode',
            '"*redacted*" AS beneficiary_city',
            'username',
            'gateway_balance',
            'merchant_id',
            'fts_fund_account_id',
            'beneficiary_state',
            '"*redacted*" AS password',
            'balance_last_fetched_at',
            'account_ifsc',
            'balance_id',
            '"*redacted*" AS beneficiary_country',
            'reference1',
            '"*redacted*" AS account_number',
            'bank_internal_reference_number',
            '"*redacted*" AS beneficiary_address1',
            'account_type',
            'status',
            '"*redacted*" AS beneficiary_name',
            '"*redacted*" AS beneficiary_address2',
            'internal_comment'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_banking_accounts_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
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
        DB::statement('DROP VIEW IF EXISTS masked_banking_accounts_view');
    }
}
