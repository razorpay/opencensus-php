<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedWorldlineView extends Migration
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
            'auth_code',
            'secondary_id',
            'payment_id',
            'ref_no',
            'created_at',
            'refund_id',
            'gateway_utr',
            'updated_at',
            'action',
            'transaction_type',
            'received',
            'bank_code',
            'mid',
            'aggregator_id',
            'txn_currency',
            'customer_vpa',
            'txn_amount',
            'primary_id'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_worldline_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::WORLDLINE;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_worldline_view');
    }
}
