<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\AccessPolicyAuthzRolesMap\Entity;

class CreateMaskedAccessPolicyAuthzRolesMapView extends Migration
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
            Entity::PRIVILEGE_ID,
            Entity::ACTION,
            Entity::AUTHZ_ROLES,
            Entity::META_DATA,
            Entity::CREATED_AT,
            Entity::UPDATED_AT
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_access_policy_authz_roles_map_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
            ' FROM ' . Table::ACCESS_POLICY_AUTHZ_ROLES_MAP;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_access_policy_authz_roles_map_view');
    }
}
