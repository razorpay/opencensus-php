<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedNodalStatementsView extends Migration
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
            'credit',
            'balance',
            'bank_name',
            'transaction_date',
            '"*redacted*" AS sender_account_number',
            'processed_on',
            '"*redacted*" AS receiver_account_number',
            'mode',
            'particulars',
            'cms',
            'bank_reference_number',
            'reference1',
            'debit',
            'reference2',
            'created_at',
            'updated_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_nodal_statements_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::NODAL_STATEMENT;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_nodal_statements_view');
    }
}
