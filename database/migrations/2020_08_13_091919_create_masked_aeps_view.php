<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedAepsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'refund_id',
            'rrn',
            'action',
            'received',
            'amount',
            'created_at',
            'error_code',
            'updated_at',
            'error_description',
            'id',
            '"*redacted*" AS aadhaar_number',
            'acquirer',
            'reversed',
            'payment_id',
            'counter'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_aeps_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::AEPS;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_aeps_view');
    }
}
