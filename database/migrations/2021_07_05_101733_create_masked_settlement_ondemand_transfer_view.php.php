<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedSettlementOndemandTransferView extends Migration
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
            'payout_id',
            'mode',
            'amount',
            'status',
            'processed_at',
            'reversed_at',
            'last_attempt_at',
            'attempts',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_settlement_ondemand_transfer_view';

        $statement = 'CREATE OR REPLACE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
            ' FROM ' . Table::SETTLEMENT_ONDEMAND_TRANSFER;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_settlement_ondemand_transfer_view');
    }
}
