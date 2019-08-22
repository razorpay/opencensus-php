<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\User\Entity as User;

class CreateQuboleUsersView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            User::ID,
            User::REMEMBER_TOKEN,
            User::CONFIRM_TOKEN,
            User::PASSWORD_RESET_TOKEN,
            User::PASSWORD_RESET_EXPIRY,
            User::CONTACT_MOBILE_VERIFIED,
            User::SECOND_FACTOR_AUTH,
            User::WRONG_2FA_ATTEMPTS,
            User::ACCOUNT_LOCKED,
            User::CREATED_AT,
            User::UPDATED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_users_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::USER;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_users_view');
    }
}
