<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedReversalsView extends Migration
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
            'entity_id',
            'tax',
            'entity_type',
            'currency',
            'balance_id',
            'notes',
            'channel',
            'transaction_id',
            'utr',
            'initiator_id',
            'customer_refund_id',
            'merchant_id',
            'amount',
            'transaction_type',
            'customer_id',
            'fee',
            'created_at',
            'updated_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_reversals_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::REVERSAL;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_reversals_view');
    }
}
