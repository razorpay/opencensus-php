<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedInvoicesView extends Migration
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
            'customer_billing_addr_id',
            'due_by',
            'user_id',
            'first_payment_min_amount',
            '"*redacted*" AS customer_contact',
            'notes',
            'customer_shipping_addr_id',
            'scheduled_at',
            'group_taxes_discounts',
            'gross_amount',
            '"*redacted*" AS customer_gstin',
            'comment',
            'expire_by',
            'receipt',
            'merchant_id',
            'issued_at',
            'callback_url',
            'tax_amount',
            '"*redacted*" AS merchant_gstin',
            'short_url',
            'big_expire_by',
            'subscription_id',
            'paid_at',
            'callback_method',
            'amount',
            'merchant_label',
            'view_less',
            'order_id',
            'batch_id',
            'cancelled_at',
            'internal_ref',
            'currency',
            'supply_state_code',
            'type',
            'customer_id',
            'idempotency_key',
            'expired_at',
            'email_status',
            '"*redacted*" AS customer_address',
            'description',
            'source',
            'entity_type',
            'billing_start',
            'status',
            'sms_status',
            '"*redacted*" AS customer_name',
            'terms',
            'entity_id',
            'billing_end',
            'subscription_status',
            'partial_payment',
            '"*redacted*" AS customer_email',
            'date',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_invoices_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::INVOICE;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_invoices_view');
    }
}
