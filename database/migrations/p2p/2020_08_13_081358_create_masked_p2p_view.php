<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedP2pView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'txn_id',
            'status',
            'error_description',
            'source_id',
            'description',
            'created_at',
            'source_type',
            'type',
            'updated_at',
            'sink_id',
            'gateway',
            'sink_type',
            'notes',
            'merchant_id',
            'currency',
            'customer_id',
            'internal_error_code',
            'id',
            'amount',
            'error_code'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_p2p_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::P2P;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_p2p_view');
    }
}
