<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedP2pBankAccountsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'ifsc',
            'updated_at',
            '"*redacted*" AS account_number',
            'masked_account_number',
            'id',
            '"*redacted*" AS beneficiary_name',
            'device_id',
            'creds',
            'handle',
            'refreshed_at',
            'gateway_data',
            'deleted_at',
            'bank_id',
            'created_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_p2p_bank_accounts_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::P2P_BANK_ACCOUNT;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_p2p_bank_accounts_view');
    }
}
