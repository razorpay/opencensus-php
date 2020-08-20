<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedAdminsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'remember_token',
            'location_code',
            'expired_at',
            'oauth_access_token',
            'disabled',
            'created_at',
            'id',
            'oauth_provider_id',
            'locked',
            'updated_at',
            'org_id',
            'user_type',
            '"*redacted*" AS old_passwords',
            'deleted_at',
            '"*redacted*" AS email',
            'employee_code',
            'last_login_at',
            'allow_all_merchants',
            'name',
            'branch_code',
            'failed_attempts',
            'username',
            'department_code',
            'password_expiry',
            '"*redacted*" AS password',
            'supervisor_code',
            'password_changed_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_admins_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::ADMIN;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_admins_view');
    }
}
