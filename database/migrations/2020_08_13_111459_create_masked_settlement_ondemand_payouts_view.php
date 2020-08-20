<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedSettlementOndemandPayoutsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'processed_at',
            'created_at',
            'id',
            'reversed_at',
            'updated_at',
            'merchant_id',
            'fees',
            'deleted_at',
            'user_id',
            'tax',
            'settlement_ondemand_id',
            'utr',
            'payout_id',
            'status',
            'mode',
            'amount',
            'initiated_at',
            'failure_reason'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_settlement_ondemand_payouts_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::SETTLEMENT_ONDEMAND_PAYOUT;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_settlement_ondemand_payouts_view');
    }
}
