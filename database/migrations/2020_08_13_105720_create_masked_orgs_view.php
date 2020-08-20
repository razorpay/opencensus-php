<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedOrgsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'type',
            'custom_code',
            'auth_type',
            'from_email',
            'email_domains',
            'signature_email',
            'allow_sign_up',
            'default_pricing_plan_id',
            'id',
            'cross_org_access',
            'created_at',
            'business_name',
            'login_logo_url',
            'updated_at',
            'display_name',
            'main_logo_url',
            'deleted_at',
            '"*redacted*" AS email',
            'invoice_logo_url'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_orgs_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
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
        DB::statement('DROP VIEW IF EXISTS masked_orgs_view');
    }
}
