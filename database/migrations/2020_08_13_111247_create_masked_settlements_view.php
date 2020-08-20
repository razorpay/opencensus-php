<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedSettlementsView extends Migration
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
            'fts_transfer_id',
            'utr',
            'transaction_id',
            'failure_reason',
            'is_new_service',
            'merchant_id',
            'balance_id',
            'remarks',
            'amount',
            'bank_account_id',
            '"*redacted*" AS fees',
            'processed_at',
            'settled_on',
            'tax',
            'channel',
            'return_utr',
            'status',
            'attempts',
            'created_at',
            'updated_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_settlements_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::SETTLEMENT;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_settlements_view');
    }
}
