<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedP2pTransactionsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'payee_type',
            'amount_minimum',
            'error_description',
            'created_at',
            'id',
            'payee_id',
            'amount_authorized',
            'internal_error_code',
            'updated_at',
            'merchant_id',
            'bank_account_id',
            'currency',
            'payer_approval_code',
            'customer_id',
            'method',
            'description',
            'payee_approval_code',
            'device_id',
            'type',
            'gateway',
            'initiated_at',
            'handle',
            'flow',
            'status',
            'expire_at',
            'payer_type',
            'mode',
            'internal_status',
            'completed_at',
            'payer_id',
            'amount',
            'error_code',
            'deleted_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_p2p_transactions_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::P2P_TRANSACTION;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_p2p_transactions_view');
    }
}
