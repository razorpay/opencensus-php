<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedCybersourceView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'action',
            'status',
            'collection_indicator',
            'processorResponse',
            'received',
            'xid',
            'cavv',
            'reason_code',
            'refund_id',
            'avsCode',
            'authorizationCode',
            'created_at',
            'auth_data',
            'cardCategory',
            'receiptNumber',
            'updated_at',
            'commerce_indicator',
            'cardGroup',
            'ref',
            'id',
            'amount',
            'cvCode',
            'capture_ref',
            'payment_id',
            'currency',
            'veresEnrolled',
            'merchantAdviceCode',
            'acquirer',
            'pares_status',
            'eci',
            'gatewayTransactionId'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_cybersource_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::CYBERSOURCE;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_cybersource_view');
    }
}
