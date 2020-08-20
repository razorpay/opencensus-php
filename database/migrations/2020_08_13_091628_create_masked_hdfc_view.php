<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedHdfcView extends Migration
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
            'status',
            'error_text',
            'gateway_transaction_id',
            'result',
            'gateway_payment_id',
            'eci',
            'action',
            'auth',
            'received',
            'ref',
            'amount',
            'avr',
            'postdate',
            'currency',
            'arn_no',
            'payment_id',
            'enroll_result',
            'error_code2',
            'created_at',
            'updated_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_hdfc_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::HDFC;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_hdfc_view');
    }
}
