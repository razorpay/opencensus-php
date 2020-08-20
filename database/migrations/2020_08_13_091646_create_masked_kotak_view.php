<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedKotakView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'TxnType',
            'MaskedCardNum',
            'updated_at',
            'TxnRefNo',
            'BatchNo',
            'OrderInfo',
            'RetRefNo',
            'Amount',
            'AuthCode',
            'Currency',
            'CaptureAmount',
            'ResponseCode',
            'RefundAmount',
            'id',
            'Message',
            'refund_id',
            'payment_id',
            'CardType',
            'created_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_kotak_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM kotak';

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_kotak_view');
    }
}
