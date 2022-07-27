<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\RoleAccessPolicyMap\Entity;

class CreateMaskedRoleAccessPolicyMapView extends Migration
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
            Entity::ROLE_ID,
            Entity::AUTHZ_ROLES,
            Entity::ACCESS_POLICY_IDS,
            Entity::CREATED_AT,
            Entity::UPDATED_AT
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_role_access_policy_map_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
            ' FROM ' . Table::ROLE_ACCESS_POLICY_MAP;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_role_access_policy_map_view');
    }
}
