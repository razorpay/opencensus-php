<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\AccessControlHistoryLogs\Entity;

class CreateMaskedAccessControlHistoryLogsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            Entity::ID,
            Entity::ENTITY_ID,
            Entity::ENTITY_TYPE,
            Entity::OWNER_ID,
            Entity::OWNER_TYPE,
            Entity::MESSAGE,
            Entity::PREVIOUS_VALUE,
            Entity::NEW_VALUE,
            Entity::CREATED_BY,
            Entity::CREATED_AT,
            Entity::UPDATED_AT
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_access_control_history_logs_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
            ' FROM ' . Table::ACCESS_CONTROL_HISTORY_LOGS;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_access_control_history_logs_view');
    }
}
