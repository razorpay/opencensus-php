<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedSettlementOndemandsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'user_id',
            'currency',
            'updated_at',
            'amount',
            'status',
            'deleted_at',
            'total_amount_settled',
            'narration',
            'total_fees',
            'notes',
            'total_tax',
            'remarks',
            'total_amount_reversed',
            'transaction_id',
            'id',
            'total_amount_pending',
            'transaction_type',
            'merchant_id',
            'max_balance',
            'created_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_settlement_ondemands_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::SETTLEMENT_ONDEMAND;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_settlement_ondemands_view');
    }
}
