<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedFirstDataView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'payment_id',
            'gateway_payment_id',
            'error_message',
            'action',
            'tdate',
            'arn_no',
            'received',
            'gateway_transaction_id',
            'created_at',
            'refund_id',
            'caps_payment_id',
            'updated_at',
            'amount',
            'endpoint_transaction_id',
            'currency',
            'gateway_terminal_id',
            'status',
            'auth_code',
            'id',
            'transaction_result',
            'approval_code'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_first_data_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::FIRST_DATA;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_first_data_view');
    }
}
