<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedPaymentAnalyticsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'checkout_id',
            'os',
            'referer',
            'risk_score',
            'os_version',
            'user_agent',
            'risk_engine',
            'device',
            'created_at',
            'attempts',
            'platform',
            'updated_at',
            'library',
            'platform_version',
            'id',
            'library_version',
            'integration',
            'payment_id',
            'browser',
            'integration_version',
            'merchant_id',
            'browser_version',
            'ip'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_payment_analytics_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::PAYMENT_ANALYTICS;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_payment_analytics_view');
    }
}
