<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedDisputesView extends Migration
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
            'base_amount',
            'gateway_dispute_id',
            'resolved_at',
            'merchant_id',
            'base_currency',
            'gateway_dispute_status',
            'raised_on',
            'parent_id',
            'gateway_amount',
            'phase',
            'expires_on',
            'payment_id',
            'gateway_currency',
            'status',
            'conversion_rate',
            'reason_id',
            'amount_deducted',
            'comments',
            'transaction_id',
            'amount_reversed',
            'deduct_at_onset',
            'amount',
            'reason_code',
            'created_at',
            'currency',
            'reason_description',
            'updated_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_disputes_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::DISPUTE;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_disputes_view');
    }
}
