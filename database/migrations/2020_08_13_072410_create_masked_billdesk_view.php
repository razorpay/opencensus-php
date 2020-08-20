<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedBilldeskView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'payment_id',
            'CurrencyType',
            'SecurityType',
            'RefDateTime',
            'updated_at',
            'action',
            'ItemCode',
            'TxnDate',
            'RefStatus',
            'received',
            'TypeField1',
            'AuthStatus',
            'RefundId',
            'MerchantID',
            'TypeField2',
            'SettlementType',
            'ErrorCode',
            'CustomerID',
            'AdditionalInfo1',
            'ErrorStatus',
            'ErrorReason',
            'TxnAmount',
            'TxnReferenceNo',
            'ErrorDescription',
            'ProcessStatus',
            'BankID',
            'BankReferenceNo',
            'RequestType',
            'refund_id',
            'id',
            '"*redacted*" AS AccountNumber',
            'BankMerchantID',
            'RefAmount',
            'created_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_billdesk_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::BILLDESK;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_billdesk_view');
    }
}
