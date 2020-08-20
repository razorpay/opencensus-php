<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedBladeView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'gateway',
            'accID',
            'received',
            'payment_id',
            'cavvAlgorithm',
            'created_at',
            'refund_id',
            'enrolled',
            'updated_at',
            'amount',
            'eci',
            'currency',
            'gateway_payment_id',
            'id',
            'status',
            'response_code',
            'action',
            'xid',
            'response_description',
            'acquirer',
            'cavv',
            'acs_url'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_blade_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::BLADE;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_blade_view');
    }
}
