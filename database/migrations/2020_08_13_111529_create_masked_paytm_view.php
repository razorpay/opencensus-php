<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedPaytmView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'orderid',
            'txndate',
            'payment_id',
            'payment_mode_only',
            'status',
            'txntype',
            'action',
            'auth_mode',
            'respcode',
            'refund_id',
            'received',
            'bank_code',
            'respmsg',
            'created_at',
            'method',
            'payment_type_id',
            'bankname',
            'updated_at',
            'request_type',
            'industry_type_id',
            'paymentmode',
            'txn_amount',
            'txnid',
            'refundamount',
            'cust_id',
            'txnamount',
            'gatewayname',
            'id',
            'channel_id',
            'banktxnid'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_paytm_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::PAYTM;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_paytm_view');
    }
}
