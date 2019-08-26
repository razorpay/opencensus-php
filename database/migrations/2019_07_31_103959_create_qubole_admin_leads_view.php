<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\AdminLead\Entity as AdminLead;

class CreateQuboleAdminLeadsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            AdminLead::ID,
            AdminLead::ADMIN_ID,
            AdminLead::ORG_ID,
            AdminLead::MERCHANT_ID,
            AdminLead::CREATED_AT,
            AdminLead::UPDATED_AT,
            AdminLead::DELETED_AT,
            AdminLead::SIGNED_UP_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_admin_leads_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::ADMIN_LEAD;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_admin_leads_view');
    }
}
