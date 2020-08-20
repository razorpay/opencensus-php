<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedWorkflowPayoutAmountRulesView extends Migration
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
            'min_amount',
            'max_amount',
            'workflow_id',
            'merchant_id',
            '`condition`',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_workflow_payout_amount_rules_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::WORKFLOW_PAYOUT_AMOUNT_RULES;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_workflow_payout_amount_rules_view');
    }
}
