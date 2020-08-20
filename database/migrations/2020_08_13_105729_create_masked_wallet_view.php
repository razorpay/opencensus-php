<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedWalletView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'wallet',
            'response_code',
            'created_at',
            'received',
            'response_description',
            'updated_at',
            '"*redacted*" AS email',
            'status_code',
            '"*redacted*" AS contact',
            'error_message',
            'id',
            'gateway_merchant_id',
            'reference1',
            'payment_id',
            'gateway_payment_id',
            'reference2',
            'action',
            'gateway_payment_id_2',
            'date',
            'amount',
            'gateway_refund_id',
            'refund_id'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_wallet_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::WALLET;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_wallet_view');
    }
}
