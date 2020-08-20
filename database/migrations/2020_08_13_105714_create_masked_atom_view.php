<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedAtomView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'received',
            'bank_name',
            'updated_at',
            'amount',
            'bank_payment_id',
            'status',
            'method',
            '"*redacted*" AS account_number',
            'error_code',
            'id',
            'gateway_payment_id',
            'gateway_result_description',
            'payment_id',
            'token',
            'callback_data',
            'refund_id',
            'success',
            'date',
            'action',
            'bank_code',
            'created_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_atom_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::ATOM;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_atom_view');
    }
}
