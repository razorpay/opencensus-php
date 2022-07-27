<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\AccessControlPrivileges\Entity;

class CreateMaskedAccessControlPrivilegesView extends Migration
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
            Entity::NAME,
            Entity::LABEL,
            Entity::DESCRIPTION,
            Entity::PARENT_ID,
            Entity::VISIBILITY,
            Entity::EXTRA_DATA,
            Entity::VIEW_POSITION,
            Entity::CREATED_AT,
            Entity::UPDATED_AT,
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_access_control_privileges_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
            ' FROM ' . Table::ACCESS_CONTROL_PRIVILEGES;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_access_control_privileges_view');
    }
}
