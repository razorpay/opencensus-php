<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedSubscriptionCyclesView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'payment_id',
            'created_at',
            'invoice_id',
            'updated_at',
            'invoice_amount',
            'status',
            'auth_attempts',
            'start',
            'id',
            'end',
            'subscription_id',
            'cycle_number'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_subscription_cycles_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::SUBSCRIPTION_CYCLE;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_subscription_cycles_view');
    }
}
