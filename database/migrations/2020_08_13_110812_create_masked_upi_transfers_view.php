<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedUpiTransfersView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            '"*redacted*" AS payer_vpa',
            'provider_reference_id',
            '"*redacted*" AS payer_account',
            'transaction_time',
            'payer_ifsc',
            'created_at',
            'id',
            'payer_bank',
            'updated_at',
            'payment_id',
            '"*redacted*" AS payee_vpa',
            'transaction_reference',
            'virtual_account_id',
            'gateway',
            'unexpected_reason',
            'expected',
            'gateway_merchant_id',
            'amount',
            'npci_reference_id'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_upi_transfers_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::UPI_TRANSFER;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_upi_transfers_view');
    }
}
