<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedSubscriptionVersionsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'quantity',
            'current_payment_id',
            'customer_notify',
            'current_start',
            'id',
            'total_count',
            'current_end',
            'subscription_id',
            'cancel_at',
            'created_at',
            'token_id',
            'start_at',
            'updated_at',
            'plan_id',
            'end_at',
            'schedule_id',
            'cycle_id',
            'status',
            'cycle_number'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_subscription_versions_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::SUBSCRIPTION_VERSION;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_subscription_versions_view');
    }
}
