<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedVirtualAccountsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'description',
            'customer_id',
            'updated_at',
            'amount_expected',
            'entity_id',
            'deleted_at',
            'amount_received',
            'entity_type',
            'bank_account_id_2',
            'id',
            'amount_paid',
            'merchant_id',
            'status',
            'amount_reversed',
            'balance_id',
            '"*redacted*" AS name',
            'bank_account_id',
            'close_by',
            'descriptor',
            'qr_code_id',
            'closed_at',
            'notes',
            'vpa_id',
            'created_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_virtual_accounts_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::VIRTUAL_ACCOUNT;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_virtual_accounts_view');
    }
}
