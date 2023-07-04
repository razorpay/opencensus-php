<?php

namespace RZP\Constants;

/**
 * Shield Constants
 */
final class Shield
{
    // Request payload constants
    const MERCHANT_ID                   = 'merchant_id';
    const ORG_ID                        = 'org_id';
    const PARTNER_IDS                   = 'partner_ids';
    const ENTITY_TYPE                   = 'entity_type';
    const ENTITY_ID                     = 'entity_id';
    const INPUT                         = 'input';
    const MERCHANT_NAME                 = 'merchant_name';
    const MERCHANT_EMAIL                = 'merchant_email';
    const MERCHANT_BUSINESS_TYPE        = 'merchant_business_type';
    const MERCHANT_BUSINESS_CATEGORY    = 'merchant_business_category';
    const MERCHANT_CATEGORY             = 'merchant_category';
    const MERCHANT_CATEGORY_CODE        = 'merchant_category_code';
    const MERCHANT_RISK_THRESHOLD       = 'merchant_risk_threshold';
    const MERCHANT_WEBSITE              = 'merchant_website';
    const MERCHANT_WHITELISTED_DOMAINS  = 'whitelisted_domains';
    const MERCHANT_WHITELISTED_APP_URLS = 'whitelisted_app_urls';
    const APPS_EXEMPT_RISK_CHECK        = 'apps_exempt_risk_check';
    const PARTNER_WHITELISTED_DOMAINS   = 'partner_whitelisted_domains';
    const MERCHANT_CREATED_AT           = 'merchant_created_at';
    const MERCHANT_ACTIVATED_AT         = 'merchant_activated_at';
    const MERCHANT_PROMOTER_PAN         = 'merchant_promoter_pan';
    const MERCHANT_BANK_ACCOUNT         = 'merchant_bank_account';
    const MERCHANT_GSTIN                = 'merchant_gstin';
    const ID                            = 'id';
    const AMOUNT                        = 'amount';
    const BASE_AMOUNT                   = 'base_amount';
    const CURRENCY                      = 'currency';
    const RECURRING                     = 'recurring';
    const RECURRING_TYPE                = 'recurring_type';
    const CONTACT                       = 'contact';
    const INTERNATIONAL                 = 'international';
    const CALLBACK_URL                  = 'callback_url';
    const EMAIL                         = 'email';
    const METHOD                        = 'method';
    const BANK                          = 'bank';
    const WALLET                        = 'wallet';
    const VPA                           = 'vpa';
    const CARD_FP                       = 'card_fingerprint';
    const CARD_IIN                      = 'card_iin';
    const CARD_NETWORK                  = 'card_network';
    const CARD_TYPE                     = 'card_type';
    const CARD_COUNTRY                  = 'card_country';
    const CARD_ISSUER                   = 'card_issuer';
    const CARD_NAME                     = 'card_name';
    const CARD_LAST4                    = 'card_last4';
    const CARD_LENGTH                   = 'card_length';
    const CARD_EXPIRY_MONTH             = 'card_expiry_month';
    const CARD_EXPIRY_YEAR              = 'card_expiry_year';
    const CARD_NUMBER                   = 'card_number';
    const IP                            = 'ip';
    const USER_AGENT                    = 'user_agent';
    const REFERER                       = 'referer';
    const BROWSER                       = 'browser';
    const BROWSER_VERSION               = 'browser_version';
    const OS                            = 'os';
    const OS_VERSION                    = 'os_version';
    const DEVICE                        = 'device';
    const PLATFORM                      = 'platform';
    const PLATFORM_VERSION              = 'platform_version';
    const LIBRARY                       = 'library';
    const LIBRARY_VERSION               = 'library_version';
    const ATTEMPTS                      = 'attempts';
    const ACCEPT_LANGUAGE               = 'accept_language';
    const CREATED_AT                    = 'created_at';
    const DEFAULT_EMAIL                 = 'void@razorpay.com';
    const DEFAULT_ACCEPT_LANGUAGE       = 'en-US';
    const CHECKOUT_ID                   = 'checkout_id';
    const FRONTEND_FP_HASH              = 'frontend_fp_hash';
    const UPI_TYPE                      = 'upi_type';
    const SUBSCRIPTION_ID               = 'subscription_id';
    const PAYMENT_LINK_ID               = 'payment_link_id';
    const ORDER_ID                      = 'order_id';
    const AUTH_TYPE                     = 'auth_type';
    const RECEIVER_TYPE                 = 'receiver_type';
    const INVOICE_TYPE                  = 'invoice_type';
    const INVOICE_ENTITY_TYPE           = 'invoice_entity_type';
    const INTEGRATION                   = 'integration';
    const INTEGRATION_VERSION           = 'integration_version';
    const RZP_CHECKOUT_LIBRARY          = 'rzp_checkout_library';
    const PACKAGE_NAME                  = 'package_name';
    const TOKEN_ID                      = 'token_id';
    const TOKEN_MAX_AMOUNT              = 'token_max_amount';
    const VIRTUAL_DEVICE_ID             = 'virtual_device_id';
    const SHOPIFY                       = 'shopify';
    const SHOPIFY_PAYMENT_APP           = 'shopify-payment-app';
    const CANCEL_URL                    = 'cancelUrl';
    const REFERER_URL                   = 'referer_url';
    const DOMAIN                        = 'domain';
    const ORDER_CANCEL_URL              = 'order_cancel_url';
    const ORDER_REFERER_URL             = 'order_referer_url';
    const ORDER_DOMAIN                  = 'order_domain';

    const IS_PARTNER_INITIATED_PAYMENT = 'is_partner_initiated_payment';
    const EARLY_SETTLEMENT_ENABLED     = 'early_settlement_enabled';

    const PAYMENT_PRODUCT               = 'payment_product';
    const PRODUCT_PAYMENT_GATEWAY       = 'payment_gateway';
    const PRODUCT_PAYMENT_LINKS         = 'payment_links';
    const PRODUCT_PAYMENT_INVOICES      = 'payment_invoices';
    const PRODUCT_PAYMENT_EPOS          = 'payment_epos';
    const PRODUCT_PAYMENT_PAGES         = 'payment_pages';
    const PRODUCT_PAYMENT_ROUTE         = 'payment_route';
    const PRODUCT_PAYMENT_SMART_COLLECT = 'payment_smart_collect';
    const TRIGGERED_RULES               = 'triggered_rules';
    const RULE_ID                       = 'rule_id';
    const RULE_CODE                     = 'rule_code';
    const RULE_DESCRIPTION              = 'rule_description';

    const BILLING_ADDRESS_LINE_1      = "billing_address_line_1";
    const BILLING_ADDRESS_LINE_2      = "billing_address_line_2";
    const BILLING_ADDRESS_CITY        = "billing_address_city";
    const BILLING_ADDRESS_STATE       = "billing_address_state";
    const BILLING_ADDRESS_COUNTRY     = "billing_address_country";
    const BILLING_ADDRESS_POSTAL_CODE = "billing_address_postal_code";
    const SECURE_3D_INTERNATIONAL     = 'secure_3d_international';

    // Response constants
    const ACTION_KEY              = 'action';
    const ACTION_ALLOW            = 'allow';
    const ACTION_REVIEW           = 'review';
    const ACTION_BLOCK            = 'block';
    const MAXMIND_SCORE           = 'maxmind_score';

    const ALLOWED_ACTIONS         = [
        self::ACTION_ALLOW,
        self::ACTION_REVIEW,
        self::ACTION_BLOCK,
    ];

    const EVALUATION_PAYLOAD    = 'evaluation_payload';
    const MOBILE_SDK = 'mobile_sdk';
    const ANDROID = 'android';

    const RULE_IDS_FOR_FRAUD_WEBSITE_MISMATCH = [
        'rule_F1fgTZ9p7tj2es',
        'rule_J2yeMfz5AxeSN6',
        'rule_IJ2Jr5h6qohz1W',
        'rule_L05lVfNrHCqtlz',
    ];

    const DOMAIN_MISMATCH_BLOCK_NOTIFY_MERCHANT = "DOMAIN_MISMATCH_BLOCK_NOTIFY_MERCHANT";

    const SHIELD_SQS = 'queue.shield_create_rule_analytics';
}
