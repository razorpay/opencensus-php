<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedPartnerConfigsView extends Migration
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
            'implicit_plan_id',
            'has_gst_certificate',
            'entity_type',
            'implicit_expiry_at',
            'revisit_at',
            'entity_id',
            'explicit_plan_id',
            'created_at',
            'origin_type',
            'explicit_refund_fees',
            'updated_at',
            'origin_id',
            'explicit_should_charge',
            'deleted_at',
            'commissions_enabled',
            'commission_model',
            'default_payment_methods',
            'settle_to_partner',
            'default_plan_id',
            'tds_percentage'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_partner_configs_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::PARTNER_CONFIG;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_partner_configs_view');
    }
}
