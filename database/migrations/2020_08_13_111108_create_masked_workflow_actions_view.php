<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedWorkflowActionsView extends Migration
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
            'permission_id',
            'approved',
            'current_level',
            'maker_id',
            'state',
            'entity_id',
            'maker_type',
            'entity_name',
            'state_changer_role_id',
            'title',
            'state_changer_id',
            'description',
            'state_changer_type',
            'workflow_id',
            'org_id',
            'created_at',
            'updated_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_workflow_actions_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::WORKFLOW_ACTION;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_workflow_actions_view');
    }
}
