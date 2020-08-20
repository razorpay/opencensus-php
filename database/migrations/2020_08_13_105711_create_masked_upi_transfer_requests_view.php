<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedUpiTransferRequestsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'npci_reference_id',
            'provider_reference_id',
            '"*redacted*" AS payee_vpa',
            'transaction_reference',
            '"*redacted*" AS payer_vpa',
            'transaction_time',
            'payer_bank',
            'request_payload',
            'id',
            'payer_account',
            'created_at',
            'gateway',
            'payer_ifsc',
            'updated_at',
            'is_created',
            'amount',
            'error_message',
            'gateway_merchant_id'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_upi_transfer_requests_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::UPI_TRANSFER_REQUEST;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_upi_transfer_requests_view');
    }
}
