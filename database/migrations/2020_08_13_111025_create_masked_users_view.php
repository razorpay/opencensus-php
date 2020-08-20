<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedUsersView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'wrong_2fa_attempts',
            'updated_at',
            'account_locked',
            '"*redacted*" AS old_password1',
            '"*redacted*" AS remember_token',
            '"*redacted*" AS old_password2',
            '"*redacted*" AS confirm_token',
            '"*redacted*" AS old_password3',
            '"*redacted*" AS email',
            '"*redacted*" AS password_reset_token',
            'id',
            '"*redacted*" AS password',
            'password_reset_expiry',
            '"*redacted*" AS name',
            '"*redacted*" AS contact_mobile',
            'contact_mobile_verified',
            'second_factor_auth',
            'created_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_users_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
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
        DB::statement('DROP VIEW IF EXISTS masked_users_view');
    }
}
