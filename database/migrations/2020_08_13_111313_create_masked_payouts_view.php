<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedPayoutsView extends Migration
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
            'mode',
            'purpose_type',
            'status',
            'utr',
            'rejected_at',
            'payout_link_id',
            'destination_id',
            'amount',
            'fts_transfer_id',
            'failure_reason',
            'queued_at',
            'pricing_rule_id',
            'reference_id',
            'destination_type',
            'currency',
            'transaction_id',
            'return_utr',
            'initiated_at',
            'scheduled_at',
            'narration',
            'fund_account_id',
            'payment_id',
            'transaction_type',
            'remarks',
            'cancelled_at',
            'scheduled_on',
            'merchant_id',
            'batch_id',
            'notes',
            'processed_at',
            'settled_on',
            'fee_type',
            'balance_id',
            'idempotency_key',
            '"*redacted*" AS fees',
            'batch_fund_transfer_id',
            'pending_at',
            'type',
            'batch_submitted_at',
            'customer_id',
            'user_id',
            'channel',
            'reversed_at',
            'method',
            'purpose',
            'tax',
            'attempts',
            'failed_at',
            'created_at',
            'updated_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_payouts_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::PAYOUT;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_payouts_view');
    }
}
