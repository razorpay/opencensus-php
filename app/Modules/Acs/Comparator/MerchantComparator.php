<?php

namespace RZP\Modules\Acs\Comparator;

class MerchantComparator extends Base
{

    protected $excludedKeys = [
        "created_at" => true,
        "updated_at" => true,
        "second_factor_auth" => true,
        "restricted" => true,
        "activated" => true,
        "activated_at" => true,
        "live" => true,
        "hold_funds" => true,
        "pricing_plan_id" => true,
        "website" => true,
        "category" => true,
        "categoryv2" => true,
        "invoice_code" => true,
        "product_international" => true,
        "channel" => true,
        "settlement_schedule" => true,
        "fee_bearer" => true,
        "fee_model" => true,
        "refund_source" => true,
        "linked_account_kyc" => true,
        "has_key_access" => true,
        "activation_source" => true,
        "auto_capture_late_auth" => true,
        "risk_rating" => true,
        "risk_threshold" => true,
        "receipt_email_enabled" => true,
        "receipt_email_trigger_event" => true,
        "max_payment_amount" => true,
        "default_refund_speed" => true,
        "convert_currency" => true,
        "free_payouts_consumed" => true,
        "max_international_payment_amount" => true,
        "audit_id" => true,
        "country_code" => true,
        "whitelisted_domains" => true
    ];

    function __construct()
    {
        parent::__construct();
    }
}
