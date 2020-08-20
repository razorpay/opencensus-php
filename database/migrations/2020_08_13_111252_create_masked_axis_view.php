<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedAxisView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'vpc_Amount',
            'vpc_3DSECI',
            'vpc_ShopTransactionNo',
            'vpc_AcqCSCRespCode',
            'id',
            'vpc_AuthorisedAmount',
            'vpc_3DSXID',
            'vpc_TransactionNo',
            'vpc_CSCResultCode',
            'payment_id',
            'vpc_CapturedAmount',
            'vpc_3DSenrolled',
            'vpc_TxnResponseCode',
            'refund_id',
            'action',
            'vpc_RefundedAmount',
            'vpc_3DSstatus',
            'vpc_VerToken',
            'arn',
            'received',
            'vpc_AcqResponseCode',
            'vpc_AuthorizeId',
            'vpc_VerType',
            'terminal_id',
            'amex',
            'vpc_Command',
            'vpc_BatchNo',
            'vpc_VerSecurityLevel',
            'created_at',
            'vpc_Currency',
            'vpc_Card',
            'vpc_VerStatus',
            'updated_at',
            'genius',
            'vpc_MerchTxnRef',
            'vpc_ReceiptNo',
            'vpc_Message'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_axis_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::MIGS;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_axis_view');
    }
}
