<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedGatewayRulesView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'id',
            'filter_type',
            'recurring',
            'min_amount',
            'authentication_gateway',
            '`load`',
            'capability',
            'max_amount',
            'auth_type',
            'merchant_id',
            'gateway_acquirer',
            'recurring_type',
            'iins',
            'step',
            'procurer',
            'network_category',
            'currency',
            'comments',
            'org_id',
            'shared_terminal',
            'method_type',
            'emi_duration',
            'created_at',
            'gateway',
            'international',
            'method_subtype',
            'emi_subvention',
            'updated_at',
            'type',
            'network',
            'card_category',
            'category',
            'deleted_at',
            '`group`',
            'method',
            'issuer',
            'category2'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_gateway_rules_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::GATEWAY_RULE;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_gateway_rules_view');
    }
}
