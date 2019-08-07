<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Admin\Entity as Admin;

class CreateQuboleAdminsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            Admin::ID,
            Admin::ORG_ID,
            Admin::EMAIL,
            Admin::NAME,
            Admin::USERNAME,
            Admin::REMEMBER_TOKEN,
            Admin::OAUTH_PROVIDER_ID,
            Admin::USER_TYPE,
            Admin::EMPLOYEE_CODE,
            Admin::BRANCH_CODE,
            Admin::DEPARTMENT_CODE,
            Admin::SUPERVISOR_CODE,
            Admin::LOCATION_CODE,
            Admin::DISABLED,
            Admin::LOCKED,
            Admin::LAST_LOGIN_AT,
            Admin::FAILED_ATTEMPTS,
            Admin::PASSWORD_EXPIRY,
            Admin::PASSWORD_CHANGED_AT,
            Admin::EXPIRED_AT,
            Admin::CREATED_AT,
            Admin::UPDATED_AT,
            Admin::DELETED_AT,
            Admin::ALLOW_ALL_MERCHANTS
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_admins_view AS
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
        DB::statement('DROP VIEW IF EXISTS qubole_admins_view');
    }
}
