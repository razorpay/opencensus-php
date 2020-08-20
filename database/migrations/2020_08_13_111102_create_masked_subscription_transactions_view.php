<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedSubscriptionTransactionsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'payment_amount',
            'refund_amount',
            'id',
            'invoice_id',
            'cycle_id',
            'payment_id',
            'type',
            'credit_note_id',
            'addon_amount',
            'created_at',
            'plan_amount',
            'updated_at',
            'unused_amount'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_subscription_transactions_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::SUBSCRIPTION_TRANSACTION;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_subscription_transactions_view');
    }
}
