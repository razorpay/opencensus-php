<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedUpiView extends Migration
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
            'bank',
            'npci_reference_id',
            'created_at',
            'gateway',
            'provider',
            'npci_txn_id',
            'updated_at',
            'payment_id',
            '"*redacted*" AS contact',
            'reconciled_at',
            'expiry_time',
            'refund_id',
            'name',
            'gateway_data',
            'action',
            'received',
            'status_code',
            'type',
            'merchant_reference',
            '"*redacted*" AS vpa',
            'amount',
            'gateway_merchant_id',
            '"*redacted*" AS account_number',
            'acquirer',
            'gateway_payment_id',
            'ifsc'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_upi_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::UPI;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_upi_view');
    }
}
