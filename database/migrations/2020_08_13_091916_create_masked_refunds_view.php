<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedRefundsView extends Migration
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
            'amount',
            'gateway',
            'settled_by',
            'receipt',
            'tax',
            'gateway_currency',
            'currency',
            'gateway_refunded',
            'bank_account_id',
            'transaction_id',
            'last_attempted_at',
            'base_amount',
            'reference9',
            'batch_fund_transfer_id',
            'processed_at',
            'status',
            'reference1',
            'reversal_id',
            'attempts',
            'is_scrooge',
            'fts_transfer_id',
            'reference2',
            'vpa_id',
            'speed_requested',
            'batch_id',
            'error_code',
            'reference3',
            'balance_id',
            'speed_decisioned',
            'payment_id',
            'internal_error_code',
            'reference4',
            'speed_processed',
            'merchant_id',
            'error_description',
            'reversed_at',
            'notes',
            'created_at',
            'updated_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_refunds_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::REFUND;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_refunds_view');
    }
}
