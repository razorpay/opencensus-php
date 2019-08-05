<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Admin\Admin\Token\Entity as AdminToken;

class CreateQuboleAdminTokensView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            AdminToken::ID,
            AdminToken::ADMIN_ID,
            AdminToken::CREATED_AT,
            AdminToken::UPDATED_AT,
            AdminToken::EXPIRES_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_admin_tokens_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::ADMIN_TOKEN;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_admin_tokens_view');
    }
}
