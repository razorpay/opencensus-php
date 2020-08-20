<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedPricingView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'plan_name',
            'payment_method_subtype',
            'account_type',
            '"*redacted*" AS max_fee',
            'product',
            'auth_type',
            'channel',
            'created_at',
            'feature',
            'payment_network',
            '"*redacted*" AS amount_range_active',
            'updated_at',
            '"*redacted*" AS type',
            'payment_issuer',
            '"*redacted*" AS amount_range_min',
            'deleted_at',
            'gateway',
            'emi_duration',
            '"*redacted*" AS amount_range_max',
            'expired_at',
            'id',
            'procurer',
            'international',
            '"*redacted*" AS percent_rate',
            'payouts_filter',
            'org_id',
            'payment_method',
            '"*redacted*" AS fee_bearer',
            '"*redacted*" AS fixed_rate',
            'plan_id',
            'payment_method_type',
            'receiver_type',
            '"*redacted*" AS min_fee'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_pricing_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::PRICING;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_pricing_view');
    }
}
