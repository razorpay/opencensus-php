<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedSubscriptionRegistrationsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'method',
            'currency',
            'entity_type',
            'max_amount',
            'entity_id',
            'auth_type',
            'status',
            'expire_at',
            'id',
            'recurring_status',
            'notes',
            'merchant_id',
            'failure_reason',
            'created_at',
            'customer_id',
            'amount',
            'updated_at',
            'token_id',
            'attempts',
            'deleted_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_subscription_registrations_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::SUBSCRIPTION_REGISTRATION;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_subscription_registrations_view');
    }
}
