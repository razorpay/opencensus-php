<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Customer\AppToken\Entity as AppToken;

class CreateQuboleCustomerAppsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            AppToken::ID,
            AppToken::MERCHANT_ID,
            AppToken::CUSTOMER_ID,
            AppToken::CREATED_AT,
            AppToken::UPDATED_AT,
            AppToken::DELETED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_customer_apps_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::APP_TOKEN;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_customer_apps_view');
    }
}
