<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedSubscriptionsView extends Migration
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
            'plan_id',
            'current_payment_id',
            'quantity',
            'notes',
            'schedule_id',
            'current_invoice_id',
            'total_count',
            'current_start',
            'charge_at',
            'customer_id',
            'current_invoice_amount',
            'paid_count',
            'current_end',
            'activated_at',
            'global_customer',
            'issued_invoices_count',
            'start_at',
            'cancelled_at',
            '"*redacted*" AS customer_email',
            'auth_attempts',
            'end_at',
            'authenticated_at',
            'customer_name',
            'customer_notify',
            'cancel_at',
            'ended_at',
            '"*redacted*" AS customer_contact',
            'status',
            'type',
            'failed_at',
            'merchant_id',
            'token_id',
            'error_status',
            'source',
            'created_at',
            'updated_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_subscriptions_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::SUBSCRIPTION;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_subscriptions_view');
    }
}
