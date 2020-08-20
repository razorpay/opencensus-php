<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedP2pUpiTransactionsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'network_transaction_id',
            'gateway_error_description',
            'gateway_transaction_id',
            'risk_scores',
            'transaction_id',
            'gateway_reference_id',
            '"*redacted*" AS payer_account_number',
            'device_id',
            'rrn',
            'payer_ifsc_code',
            'handle',
            'ref_id',
            '"*redacted*" AS payee_account_number',
            'gateway_data',
            'ref_url',
            'payee_ifsc_code',
            'action',
            'mcc',
            'created_at',
            'status',
            'gateway_error_code',
            'updated_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_p2p_upi_transactions_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::P2P_UPI_TRANSACTION;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_p2p_upi_transactions_view');
    }
}
