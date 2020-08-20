<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedEmiPlansView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'bank',
            'merchant_payback',
            'network',
            'created_at',
            'rate',
            'updated_at',
            'duration',
            'deleted_at',
            'methods',
            'type',
            'min_amount',
            'id',
            'issuer_plan_id',
            'merchant_id',
            'subvention'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_emi_plans_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::EMI_PLAN;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_emi_plans_view');
    }
}
