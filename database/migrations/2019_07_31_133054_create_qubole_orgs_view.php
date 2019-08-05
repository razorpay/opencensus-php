<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Org\Entity as Org;

class CreateQuboleOrgsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            Org::ID,
            Org::DISPLAY_NAME,
            Org::BUSINESS_NAME,
            Org::TYPE,
            Org::AUTH_TYPE,
            Org::EMAIL_DOMAINS,
            Org::ALLOW_SIGN_UP,
            Org::CROSS_ORG_ACCESS,
            Org::LOGIN_LOGO_URL,
            Org::MAIN_LOGO_URL,
            Org::INVOICE_LOGO_URL,
            Org::CUSTOM_CODE,
            Org::SIGNATURE_EMAIL,
            Org::DEFAULT_PRICING_PLAN_ID,
            Org::CREATED_AT,
            Org::UPDATED_AT,
            Org::DELETED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_orgs_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::ORG;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_orgs_view');
    }
}
