<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedCommissionsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'debit',
            'notes',
            'id',
            'credit',
            'created_at',
            'source_type',
            'currency',
            'updated_at',
            'source_id',
            'fee',
            'partner_id',
            'tax',
            'partner_config_id',
            'transaction_id',
            'type',
            'record_only',
            'status',
            'model'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_commissions_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::COMMISSION;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_commissions_view');
    }
}
