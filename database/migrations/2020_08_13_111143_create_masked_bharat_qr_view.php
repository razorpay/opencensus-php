<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedBharatQrView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            '"*redacted*" AS vpa',
            'transaction_date',
            'id',
            '"*redacted*" AS card_number',
            'gateway_terminal_id',
            'payment_id',
            'card_network',
            'gateway_terminal_desc',
            'virtual_account_id',
            'provider_reference_id',
            '"*redacted*" AS customer_name',
            'expected',
            'merchant_reference',
            'status_code',
            'gateway_merchant_id',
            'trace_number',
            'created_at',
            'method',
            'rrn',
            'updated_at',
            'amount',
            'transaction_time'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_bharat_qr_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::BHARAT_QR;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_bharat_qr_view');
    }
}
