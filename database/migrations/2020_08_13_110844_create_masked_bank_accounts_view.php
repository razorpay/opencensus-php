<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedBankAccountsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'id',
            'merchant_id',
            'entity_id',
            'ifsc_code',
            '"*redacted*" AS beneficiary_address4',
            '"*redacted*" AS beneficiary_mobile',
            '"*redacted*" AS account_number',
            'mobile_banking_enabled',
            'fts_fund_account_id',
            'account_type',
            'mpin',
            '`virtual`',
            '"*redacted*" AS beneficiary_name',
            '"*redacted*" AS beneficiary_city',
            'notes',
            '"*redacted*" AS registered_beneficiary_name',
            'beneficiary_state',
            '"*redacted*" AS beneficiary_address1',
            'beneficiary_country',
            'type',
            '"*redacted*" AS beneficiary_address2',
            'beneficiary_pin',
            'deleted_at',
            'beneficiary_code',
            '"*redacted*" AS beneficiary_address3',
            '"*redacted*" AS beneficiary_email',
            'created_at',
            'updated_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_bank_accounts_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
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
        DB::statement('DROP VIEW IF EXISTS masked_bank_accounts_view');
    }
}
