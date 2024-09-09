<?php

namespace RZP\Models\Merchant\OneClickCheckout;

class Constants
{
    // for future use
    const PLATFORM          = 'platform';
    const SHIPPING_INFO_URL = 'shipping_info_url';
    const FETCH_COUPONS_URL = 'fetch_coupons_url';
    const APPLY_COUPON_URL  = 'apply_coupon_url';
    const ORDER_STATUS_UPDATE_URL = 'order_status_update_url';
    const DOMAIN_URL = 'domain_url';

    // supported platform types
    const NATIVE            = 'native';
    const WOOCOMMERCE       = 'woocommerce';
    const SHOPIFY           = 'shopify';
    const MAGENTO           = 'magento';

    const SHOPIFY_TEMP_RECEIPT = 'Order Pending';
    const SHOPIFY_GC_FAILED_RECEIPT = 'GC Payment Failed';

    const COD_INTELLIGENCE                  = 'cod_intelligence';
    const ONE_CLICK_CHECKOUT                = 'one_click_checkout';
    const ONE_CC_AUTO_FETCH_COUPONS         = 'one_cc_auto_fetch_coupons';
    const ONE_CC_BUY_NOW_BUTTON             = 'one_cc_buy_now_button';
    const ONE_CC_INTERNATIONAL_SHIPPING     = 'one_cc_international_shipping';
    const ONE_CC_CAPTURE_BILLING_ADDRESS    = 'one_cc_capture_billing_address';
    const ONE_CC_GA_ANALYTICS               = 'one_cc_ga_analytics';
    const ONE_CC_FB_ANALYTICS               = 'one_cc_fb_analytics';
    const MANUAL_CONTROL_COD_ORDER          = 'manual_control_cod_order';
    const ONE_CC_CAPTURE_GSTIN              = 'one_cc_capture_gstin';
    const ONE_CC_CAPTURE_ORDER_INSTRUCTIONS = 'one_cc_capture_order_instructions';
    const ONE_CC_ADDRESS_SYNC_OFF           = 'one_cc_address_sync_off';
    const ONE_CC_WHITELIST_COUPONS          = 'one_cc_whitelist_coupons';
    const ONE_CC_CACHE_COUPONS              = 'one_cc_cache_coupons';
    const ONE_CC_HANDLE_DIGITAL_PRODUCT     = 'one_cc_handle_digital_product';
    const ONE_CC_TAX_INCLUSION              = 'one_cc_tax_inclusion';
    const ONE_CC_OPT_SHIPPING_TAX          = 'one_cc_opt_shipping_tax';
    const ONE_CC_SHOPIFY_MULTIPLE_SHIPPING = 'one_cc_multiple_shipping';
    const ONE_CC_GUPSHUP_CREDENTIALS        = 'one_cc_gupshup_credentials';
    const ONE_CC_ENABLE_GUPSHUP             = 'one_cc_enable_gupshup';
    const COD_ENGINE                        = 'cod_engine';
    const COD_ENGINE_TYPE                   = 'cod_engine_type';
    const ONE_CC_PREPAY_COD_CONVERSION     = 'one_cc_prepay_cod_conversion';
    // ONE_CC_PARTIAL_PAYMENTS_COD allows a merchant to charge an upfront prepaid amount to a customer
    // and the remaining as cod to reduce the RTOs.
    const ONE_CC_PARTIAL_PAYMENTS_COD     = 'one_cc_partial_payments_cod';

    // Coupon-Engine OneCC Config controls whether the merchant wants to use
    // coupon-engine's features (or) not.
    // Coupon-Engine flows/features include:
    // 1. Coupon settings and coupon tab on merchant dashboard
    // 2. Manual one-time sync coupon button/tab on merchant dashboard
    // 3. Auto-Sync coupons from Shopify
    // 4. Fallback to Shopify in coupon apply/evaluate failure
    // NOTE: Whether we (RZP) want to give the merchant this feature (or) not
    // is controlled by a feature flag i.e. if feature flag is false then this
    // config being true has no effect and merchant won't be able to use
    // coupon-engine features.
    const ONE_CC_COUPON_ENGINE              = 'one_cc_coupon_engine';

    // Auto-Apply Coupons OneCC Config controls whether the merchant wants to use
    // auto-apply the best auto-applicable coupons on checkout feature (or) not.
    // NOTE: Whether we (RZP) want to give the merchant this feature (or) not
    // is controlled by a feature flag i.e. if feature flag is false then this
    // config being true has no effect and merchant won't be able to use
    // auto-apply coupons feature.
    const ONE_CC_AUTO_APPLY_COUPONS         = 'one_cc_auto_apply_coupons';

    // Multi-Coupons OneCC Config controls whether the merchant wants to use
    // multiple combinable coupons feature (or) not.
    // NOTE: Whether we (RZP) want to give the merchant this feature (or) not
    // is controlled by a feature flag i.e. if feature flag is false then this
    // config being true has no effect and merchant won't be able to use
    // multi-coupons feature.
    const ONE_CC_MULTI_COUPONS              = 'one_cc_multi_coupons';

    // Shipping, serviceability and cod engine settings in MCS.

    const SHIPPING_ENGINE  = 'shipping_engine';

    // Settings and workflows defined for retargeting engine.
    const RETARGETING_SETTINGS             = "retargeting_settings";

    // Need to remove once sopc merchant dashboard changes are live
    const RAZORPAY_COD = "rcod";

    // A config to maintain what are the apps installed by merchant
    // Currently this will be only applicable to shopify and
    // hold value as 1 and the value json will hold value either one or both
    // of magic_checkout ond rcod in array format.
    // This Config will be populated at the time when merchant installs
    // Razorpay COD App. This and dashboard_view config will be
    // collectively used by FE to decide which view should be rendered
    // for merchants in merchant dashboard.
    const APPS_INSTALLED = 'apps_installed';

    const SOPC_METAFIELDS = 'sopc_metafields';
    const SHOP_PLAN_NAME =  'shop_plan_name';
    // It holds the value either magic_checkout or razorpay_cod and
    // will be updated by FE depending on merchant toggles across apps
    // in merchant dashboard and the same app UI for merchant dashboard
    // will be rendered on next visit of merchant.
    const DASHBOARD_VIEW = 'dashboard_view';

    // gift card configs
    const ONE_CC_GIFT_CARD                 = 'one_cc_gift_card';
    const ONE_CC_GIFT_CARD_RESTRICT_COUPON = 'one_cc_gift_card_restrict_coupon';
    const ONE_CC_BUY_GIFT_CARD             = 'one_cc_buy_gift_card';
    const ONE_CC_MULTIPLE_GIFT_CARD        = 'one_cc_multiple_gift_card';
    const ONE_CC_GIFT_CARD_COD_RESTRICT    = 'one_cc_gift_card_cod_restrict';

    // common auth keys
    const API_KEY        = 'api_key';
    const API_SECRET     = 'api_secret';
    const OAUTH_TOKEN    = 'oauth_token'; // NOTE: Or ACCESS_TOKEN
    const REFRESH_TOKEN  = 'refresh_token';
    const USERNAME       = 'username';
    const PASSWORD       = 'password';

    // shopify auth headers
    const X_SHOPIFY_ACCESS_TOKEN            = 'X-Shopify-Access-Token';
    const X_SHOPIFY_STOREFRONT_ACCESS_TOKEN = 'X-Shopify-Storefront-Access-Token';

    // shopify auth keys
    const STOREFRONT_ACCESS_TOKEN           = 'storefront_access_token';
    const SHOP_ID                           = 'shop_id';
    const ACCESS_TOKEN                      = 'access_token';
    const CLIENT_SECRET                     = 'client_secret';
    const CLIENT_ID                         = 'client_id';
    const DELEGATE_ACCESS_TOKEN             = 'delegate_access_token';
    const ADMIN_ACCESS_TOKEN                = 'admin_access_token';
    const APP_NAME                          = 'app_name';

    // shopify supported app names
    const SOPC_APP                          = 'sopc';

    // shopify api types
    const STOREFRONT                        = 'storefront';
    const ADMIN_REST                        = 'admin_rest';
    const ADMIN_GRAPHQL                     = 'admin_graphql';

    // entities
    const ORDER_ID                          = 'order_id';

    // merchant flows
    const DISABLE_MAGIC_CHECKOUT = 'disable_magic_checkout';
    const DISABLE_MAGIC_CHECKOUT_ADDITIONAL_COMMENT = 'disable_magic_checkout_additional_comment';

    // address ingestion
    const ONE_CC_ONBOARDED_TIMESTAMP = 'one_cc_onboarded_timestamp';
    const ONE_CC_ADDRESS_INGESTION_JOB = 'job';

    const ONE_CC_SHIPPING_USING_CHECKOUT = "one_cc_shipping_using_checkout";

    const ONE_CC_HIDE_COD_WHEN_DISABLED = "one_cc_hide_cod_when_disabled";

    const WALLET_PAYMENT        = "wallet_payment";
    const WALLETS               = "wallets";
    const CAPILLARY_WALLET      = "capillary_wallet";
    const NAME                  = "name";
    const ALLOW_CUSTOMER_INPUT  = "allow_customer_input";
    const CREDENTIALS           = "credentials";

    const TERRA_WALLET = "terra_wallet";
    const TERRA_WALLET_VALUE = "terra_wallet_value";

    const ONE_CC_SHOPIFY_DRAFT_ORDER = 'one_cc_draft_order';
    const ONE_CC_SHOPIFY_ACC_CREATE = 'one_cc_shopify_acc_create';

    const SHOPIFY_API_TYPES = [
        self::STOREFRONT,
        self::ADMIN_REST,
        self::ADMIN_GRAPHQL,
    ];

    const SHOPIFY_AUTH = [
        self::API_KEY,
        self::API_SECRET,
        self::OAUTH_TOKEN,
        self::STOREFRONT_ACCESS_TOKEN,
        self::SHOP_ID,
        self::DELEGATE_ACCESS_TOKEN,
    ];

    const SHOPIFY_AUTH_ENCRYPT = [
        self::API_SECRET,
        self::STOREFRONT_ACCESS_TOKEN,
        self::OAUTH_TOKEN,
        self::DELEGATE_ACCESS_TOKEN,
    ];

    const WOOCOMMERCE_AUTH = [
        self::API_KEY,
        self::API_SECRET,
    ];

    const WOOCOMMERCE_AUTH_ENCRYPT = [
        self::API_SECRET,
    ];

    const NATIVE_AUTH = [
        self::USERNAME,
        self::PASSWORD,
    ];

    const NATIVE_AUTH_ENCRYPT = [
        self::USERNAME,
        self::PASSWORD,
    ];

    const ENCRYPTED_FIELDS = [
        self::API_SECRET,
        self::STOREFRONT_ACCESS_TOKEN,
        self::OAUTH_TOKEN,
        self::DELEGATE_ACCESS_TOKEN,
        self::USERNAME,
        self::PASSWORD,
    ];

    /**
     * Note: Any new 1CC configs added also need to be added into the proto
     *       files & `internal/preferences/transformer/one_cc_config.go` of
     *       checkout service.
     *
     * @var string[]
     */
    const CONFIG_FLAGS = [
        self::COD_INTELLIGENCE,
        self::ONE_CLICK_CHECKOUT,
        self::ONE_CC_GA_ANALYTICS,
        self::ONE_CC_FB_ANALYTICS,
        self::ONE_CC_CAPTURE_BILLING_ADDRESS,
        self::ONE_CC_INTERNATIONAL_SHIPPING,
        self::ONE_CC_BUY_NOW_BUTTON,
        self::ONE_CC_AUTO_FETCH_COUPONS,
        self::MANUAL_CONTROL_COD_ORDER,
        self::ONE_CC_CAPTURE_GSTIN,
        self::ONE_CC_CAPTURE_ORDER_INSTRUCTIONS,
        self::ONE_CC_GIFT_CARD,
        self::ONE_CC_GIFT_CARD_RESTRICT_COUPON,
        self::ONE_CC_BUY_GIFT_CARD,
        self::ONE_CC_MULTIPLE_GIFT_CARD,
        self::ONE_CC_GIFT_CARD_COD_RESTRICT,
        self::ONE_CC_ADDRESS_SYNC_OFF,
        self::ONE_CC_WHITELIST_COUPONS,
        self::ONE_CC_CACHE_COUPONS,
        self::ONE_CC_HANDLE_DIGITAL_PRODUCT,
        self::ONE_CC_ENABLE_GUPSHUP,
        self::COD_ENGINE,
        self::ONE_CC_PREPAY_COD_CONVERSION,
        self::ONE_CC_COUPON_ENGINE,
        self::ONE_CC_MULTI_COUPONS,
        self::ONE_CC_AUTO_APPLY_COUPONS,
        self::RETARGETING_SETTINGS,
        self::SHIPPING_ENGINE,
        self::ONE_CC_SHIPPING_USING_CHECKOUT,
        self::ONE_CC_HIDE_COD_WHEN_DISABLED,
        self::ONE_CC_PARTIAL_PAYMENTS_COD,
    ];

    /**
     * Note: Any new configs added in this list would also need adding business
     *       logic to override features response in
     *       `internal/preferences/transformer/one_cc_config.go` of checkout-service.
     *
     * @var string[]
     */
    const WOOC_SPECIFIC_CONFIGS = [
        self::COD_ENGINE,
        self::TERRA_WALLET,
    ];

    const CONFIG_CUM_FEATURE_FLAGS = [
        self::ONE_CLICK_CHECKOUT,
        self::ONE_CC_GA_ANALYTICS,
        self::ONE_CC_FB_ANALYTICS,
    ];

    const GIFT_CARD_CONFIGS = [
        self::ONE_CC_GIFT_CARD,
        self::ONE_CC_GIFT_CARD_RESTRICT_COUPON,
        self::ONE_CC_BUY_GIFT_CARD,
        self::ONE_CC_MULTIPLE_GIFT_CARD,
        self::ONE_CC_GIFT_CARD_COD_RESTRICT,
    ];

    const SHOPIFY_SPECIFIC_CONFIGS = [
        self::ONE_CLICK_CHECKOUT,
        self::ONE_CC_GA_ANALYTICS,
        self::ONE_CC_FB_ANALYTICS,
        self::ONE_CC_BUY_NOW_BUTTON,
        self::COD_ENGINE,
        self::ONE_CC_SHIPPING_USING_CHECKOUT,
        self::SHOP_PLAN_NAME,
        self::SOPC_METAFIELDS,
    ];

    const COMMON_CONFIGS = [
       self::COD_INTELLIGENCE,
       self::ONE_CC_AUTO_FETCH_COUPONS,
       self::ONE_CC_CAPTURE_BILLING_ADDRESS,
       self::ONE_CC_INTERNATIONAL_SHIPPING,
       self::MANUAL_CONTROL_COD_ORDER,
       self::ONE_CC_CAPTURE_GSTIN,
       self::ONE_CC_CAPTURE_ORDER_INSTRUCTIONS,
       self::ONE_CC_PREPAY_COD_CONVERSION,
       self::SHIPPING_ENGINE,
       self::ONE_CC_COUPON_ENGINE,
       self::ONE_CC_HIDE_COD_WHEN_DISABLED,
       self::ONE_CC_PARTIAL_PAYMENTS_COD,
    ];

    public const COUPON_CONFIGS = [
        self::ONE_CC_AUTO_APPLY_COUPONS,
        self::ONE_CC_COUPON_ENGINE,
        self::ONE_CC_MULTI_COUPONS,
    ];

    const INTELLIGENCE_CONFIGS = [
        self::ONE_CC_PREPAY_COD_CONVERSION,
        self::COD_INTELLIGENCE,
        self::MANUAL_CONTROL_COD_ORDER,
        self::ONE_CC_PARTIAL_PAYMENTS_COD,
    ];

    const ALL_RISK_CATEGORIES = [
        self::HIGH,
        self::MEDIUM,
        self::LOW,
    ];

    const INTERNAL_CONFIGS = [
        self::ONE_CC_ADDRESS_SYNC_OFF,
        self::ONE_CC_SHOPIFY_MULTIPLE_SHIPPING,
        // Tax settings for Shopify.
        self::ONE_CC_OPT_SHIPPING_TAX,
        self::ONE_CC_TAX_INCLUSION,
        // Custom workflows post Complete Checkout for Shopify.
        self::ONE_CC_SHOPIFY_ACC_CREATE,
        self::ONE_CC_ENABLE_GUPSHUP,
        // To force use draft order for Shopify merchants - currently not in use.
        self::ONE_CC_SHOPIFY_DRAFT_ORDER
    ];

    const SHOPIFY_RESETTABLE_CONFIGS = [
        self::PLATFORM,
        self::SHOP_ID,
        self::MANUAL_CONTROL_COD_ORDER,
        self::DOMAIN_URL,
    ];

    const GENERIC_RESETTABLE_CONFIGS = [
        self::COD_ENGINE,
        self::COD_ENGINE_TYPE,
    ];

    const NATIVE_RESETTABLE_CONFIGS = [
        self::PLATFORM,
        self::SHIPPING_INFO_URL,
        self::FETCH_COUPONS_URL,
        self::APPLY_COUPON_URL,
        self::ORDER_STATUS_UPDATE_URL,
        self::MANUAL_CONTROL_COD_ORDER,
        self::DOMAIN_URL,
    ];

    const RTO_MLMODEL_ASSIGNMENT   = 'rto-mlmodel-assignment-events';
    const ONE_CC_MERCHANT_CONFIG = 'one-cc-merchant-config-events';

    const ONE_CLICK_CHECKOUT_ENABLED = "oneClickCheckoutEnabled";
    const BUY_NOW_ENABLED = "buyNowEnabled";
    const MAGIC_CHECKOUT = "magic_checkout";
    const BOOLEAN = "boolean";
    const FALSE = "false";
    const TRUE = "true";

    const KEY = "key";
    const NAMESPACE = "namespace";
    const TYPE = "type";
    const VALUE = "value";
    const METAFIELD = "metafield";

    const POST = "POST";
    const GET  = "GET";
    const METAFIELD_ENDPOINT = "/metafields.json";

    //action name for rto recommendation
    const ACTION        = 'action';
    const APPROVE       = 'approve';
    const HOLD          = 'hold';
    const CANCEL        = 'cancel';
    const CANCEL_ORDER_ENDPOINT = "/cancel.json";

    const CUSTOMER_SEARCH_ENDPOINT = "/customers/search.json";

    const ID                = 'id';
    const MERCHANT_ID        = 'merchant_id';
    const MODE              = 'mode';

    const GIFT_CARD_NUMBER = 'gift_card_number';
    const GIFT_CARD_REFERENCE_ID = 'gift_card_reference_id';
    const GIFT_CARD_BALANCE = 'balance';
    const GIFT_CARD_PARTIAL_REDEMPTION = 'allowedPartialRedemption';

    // multiple gift card number in remove gift card api
    const GIFT_CARD_NUMBERS  = 'gift_card_numbers';

    // used for getting merchant keys
    const KEYS = 'keys';

    //can be used for all 1cc apis,operation
    const MUTEX_PREFIX_1CC_OPERATION = "1cc_order:";

    // pricing object constants
    const TOTAL_COUPON_VALUE = 'total_coupon_value';
    const TOTAL_GIFT_CARD_VALUE = 'total_gift_card_value';
    const FINAL_ADJUSTED_COD_VALUE = 'final_adjusted_cod_value';
    const TOTAL_TAX_APPLIED        = 'total_tax_applied';
    const NECTOR_COINS_APPLIED     = 'nector_coins_applied';

    const NECTOR_COINS             = 'nector_coins';
    const FLITS_COINS              = 'flits_coins';

    const ORDER_STATUS_REFUNDED = 'refunded';

    // Header key for sending MID to Magic Checkout microservice for internal routes.
    const X_Merchant_Id = 'X-Merchant-Id';

    //Prepay COD constants
    const CONFIGS = 'configs';
    const ENABLED = 'enabled';
    const DISCOUNT = 'discount';
    const RISK_CATEGORY = 'risk_category';
    const COMMUNICATION = 'communication';
    const MAX_DISCOUNT = 'max_discount';
    const DISCOUNT_PERCENTAGE = 'discount_percentage';
    const MINIMUM_ORDER_VALUE = 'minimum_order_value';
    const EXPIRE_SECONDS = 'expire_seconds';
    const METHODS = 'methods';
    const FLAT = 'flat';
    const ZERO = 'zero';
    const PERCENTAGE = 'percentage';
    const HIGH = 'high';
    const MEDIUM = 'medium';
    const LOW = 'low';
    const PREPAID_PAYMENT_AMOUNT = 'prepaid_payment_amount';

    // Partial COD constants
    const RULES = 'rules';
    const CUSTOMER_RISK_CATEGORY = 'customer_risk_category';
    const MIN_ORDER_AMOUNT = 'min_order_amount';
    const MAX_ORDER_AMOUNT = 'max_order_amount';

    const TYPE_COD_FEE_COUPON = 'cod_fee';

    // AppType is a tenancy key used when installation Shopify app.
    // Each app installation will generate it's own set of access tokens and scopes.
    // As this value is shared with FE, we are not adding the full prefix.
    const SHOPIFY_APP_TYPE_MAGIC_CHECKOUT = "magic_checkout";
    const SHOPIFY_APP_TYPE_SOPC           = 'sopc';
}
