<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedWorkflowStepsView extends Migration
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
            'deleted_at',
            'role_id',
            'workflow_id',
            'reviewer_count',
            'op_type',
            'level',
            'created_at',
            'updated_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_workflow_steps_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::WORKFLOW_STEP;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_workflow_steps_view');
    }
}
