<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedIsgView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'secondary_id',
            'status_code',
            'bank_reference_no',
            'status_desc',
            'id',
            '"*redacted*" AS merchant_pan',
            'created_at',
            'payment_id',
            'transaction_date_time',
            'updated_at',
            'refund_id',
            'amount',
            'action',
            'auth_code',
            'received',
            'rrn',
            'merchant_reference',
            'tip_amount'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_isg_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::ISG;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_isg_view');
    }
}
