<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedMerchantsView extends Migration
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
            'name',
            'archived_at',
            'category',
            'invoice_code',
            'partner_type',
            'invoice_label_field',
            'convert_currency',
            '"*redacted*" AS email',
            'suspended_at',
            'whitelisted_ips_live',
            'notes',
            '"*redacted*" AS transaction_report_email',
            'brand_color',
            'risk_rating',
            'second_factor_auth',
            'live',
            'whitelisted_ips_test',
            'org_id',
            'fee_bearer',
            'handle',
            'risk_threshold',
            'restricted',
            'live_disable_reason',
            'whitelisted_domains',
            'international',
            'fee_model',
            'activation_source',
            'receipt_email_enabled',
            'external_id',
            'parent_id',
            'hold_funds',
            'dashboard_whitelisted_ips_live',
            'billing_label',
            'fee_credits_threshold',
            'business_banking',
            'receipt_email_trigger_event',
            'product_international',
            'legal_entity_id',
            'hold_funds_reason',
            'dashboard_whitelisted_ips_test',
            'display_name',
            'refund_source',
            'auto_capture_late_auth',
            'max_payment_amount',
            'activated',
            'pricing_plan_id',
            'partnership_url',
            'channel',
            'linked_account_kyc',
            'logo_url',
            'auto_refund_delay',
            'activated_at',
            'website',
            'category2',
            'has_key_access',
            'icon_url',
            'default_refund_speed',
            'signup_source',
            'created_at',
            'updated_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_merchants_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::MERCHANT;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_merchants_view');
    }
}
