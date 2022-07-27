<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Roles\Entity;

class CreateMaskedAccessControlRolesView extends Migration
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
            Entity::DESCRIPTION,
            Entity::MERCHANT_ID,
            Entity::TYPE,
            Entity::ORG_ID,
            Entity::PRODUCT,
            Entity::CREATED_BY,
            Entity::UPDATED_BY,
            Entity::CREATED_AT,
            Entity::UPDATED_AT,
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_access_control_roles_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
            ' FROM ' . Table::ACCESS_CONTROL_ROLES;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_access_control_roles_view');
    }
}
