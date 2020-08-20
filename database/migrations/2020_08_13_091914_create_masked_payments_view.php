<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedPaymentsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'currency',
            'transfer_id',
            'refund_status',
            'emi_plan_id',
            '"*redacted*" AS app_token',
            'reference13',
            'notes',
            'authentication_gateway',
            'receiver_type',
            '"*redacted*" AS tax',
            'disputed',
            'base_amount',
            'payment_link_id',
            'description',
            'error_code',
            'emi_subvention',
            'reference14',
            'transaction_id',
            'reference1',
            'reference9',
            'otp_attempts',
            'created_at',
            'method',
            'international',
            'card_id',
            'internal_error_code',
            '"*redacted*" AS token_id',
            'settled_by',
            'authorized_at',
            'reference2',
            'signed',
            'otp_count',
            'updated_at',
            'status',
            'amount_authorized',
            'bank',
            'error_description',
            'auth_type',
            'reference16',
            'auto_captured',
            'reference3',
            'verified',
            'recurring',
            'public_key',
            'two_factor_auth',
            'amount_refunded',
            'wallet',
            'cancellation_reason',
            'acknowledged_at',
            'reference17',
            'gateway_captured',
            'cps_route',
            'verify_bucket',
            'recurring_type',
            'id',
            'order_id',
            'base_amount_refunded',
            'vpa',
            'customer_id',
            'verify_at',
            '"*redacted*" AS global_token_id',
            'captured_at',
            'reference5',
            'callback_url',
            'save',
            'merchant_id',
            'invoice_id',
            'amount_paidout',
            'on_hold',
            'global_customer_id',
            'refund_at',
            '"*redacted*" AS email',
            'gateway',
            'reference6',
            '"*redacted*" AS fee',
            'late_authorized',
            'amount',
            'subscription_id',
            'amount_transferred',
            'on_hold_until',
            'receiver_id',
            'fee_bearer',
            '"*redacted*" AS contact',
            'terminal_id',
            'batch_id',
            'mdr',
            'convert_currency'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_payments_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::PAYMENT;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_payments_view');
    }
}
