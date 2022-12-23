<?php

namespace RZP\Modules\Acs\Comparator;

class MerchantComparator extends Base
{

    protected $excludedKeys = [
        "second_factor_auth" => true,
        "restricted" => true,
        "activated" => true,
        "activated_at" => true,
        "live" => true,
        "live_disable_reason" => true,
        "hold_funds" => true,
        "hold_funds_reason" => true,
        "pricing_plan_id" => true,
        "website" => true,
        "category" => true,
        "category2" => true,
        "invoice_code" => true,
        "international" => true,
        "product_international" => true,
        "channel" => true,
        "settlement_schedule" => true,
        "settlement_schedule_id" => true,
        "fee_bearer" => true,
        "fee_model" => true,
        "fee_credits_threshold" => true,
        "refund_source" => true,
        "linked_account_kyc" => true,
        "has_key_access" => true,
        "partner_type" => true,
        "handle" => true,
        "activation_source" => true,
        "business_banking" => true,
        "auto_capture_late_auth" => true,
        "invoice_label_field" => true,
        "risk_rating" => true,
        "risk_threshold" => true,
        "receipt_email_enabled" => true,
        "receipt_email_trigger_event" => true,
        "max_payment_amount" => true,
        "auto_refund_delay" => true,
        "default_refund_speed" => true,
        "convert_currency" => true,
        "whitelisted_domains" => true,
        "partnership_url" => true,
        "created_at" => true,
        "updated_at" => true,
        "external_id" => true,
        "free_payouts_consumed" => true,
        "free_payouts_consumed_last_reset_at" => true,
        "refund_credits_threshold" => true,
        "serviceability_url" => true,
        "fetch_coupons_url" => true,
        "coupon_validity_url" => true,
        "max_international_payment_amount" => true,
        "balance_threshold" => true,
        "audit_id" => true,
        "country_code" => true
    ];

    function __construct()
    {
        parent::__construct();
    }
}
