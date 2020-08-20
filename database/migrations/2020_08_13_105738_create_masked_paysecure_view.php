<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedPaysecureView extends Migration
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
            'refund_id',
            'flow',
            'status',
            'hkey',
            'gateway_transaction_id',
            'auth_not_required',
            'error_code',
            'apprcode',
            'error_message',
            'payment_id',
            'rrn',
            'action',
            'tran_date',
            'received',
            'tran_time',
            'created_at',
            'updated_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_paysecure_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::PAYSECURE;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_paysecure_view');
    }
}
