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

    const COD_INTELLIGENCE               = 'cod_intelligence';
    const ONE_CLICK_CHECKOUT             = 'one_click_checkout';
    const ONE_CC_AUTO_FETCH_COUPONS      = 'one_cc_auto_fetch_coupons';
    const ONE_CC_BUY_NOW_BUTTON          = 'one_cc_buy_now_button';
    const ONE_CC_INTERNATIONAL_SHIPPING  = 'one_cc_international_shipping';
    const ONE_CC_CAPTURE_BILLING_ADDRESS = 'one_cc_capture_billing_address';
    const ONE_CC_GA_ANALYTICS            = 'one_cc_ga_analytics';
    const ONE_CC_FB_ANALYTICS            = 'one_cc_fb_analytics';
    const MANUAL_CONTROL_COD_ORDER         = 'manual_control_cod_order';
    const ONE_CC_CAPTURE_GSTIN             = 'one_cc_capture_gstin';
    const ONE_CC_CAPTURE_ORDER_INSTRUCTIONS = 'one_cc_capture_order_instructions';
    const ONE_CC_ADDRESS_SYNC_OFF        = 'one_cc_address_sync_off';

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

    // shopify api types
    const STOREFRONT                        = 'storefront';
    const ADMIN_REST                        = 'admin_rest';
    const ADMIN_GRAPHQL                     = 'admin_graphql';

    // entities
    const ORDER_ID                          = 'order_id';

    // merchant flows
    const DISABLE_MAGIC_CHECKOUT = 'disable_magic_checkout';
    const DISABLE_MAGIC_CHECKOUT_ADDITIONAL_COMMENT = 'disable_magic_checkout_additional_comment';

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
    ];

    const SHOPIFY_AUTH_ENCRYPT = [
        self::API_SECRET,
        self::STOREFRONT_ACCESS_TOKEN,
        self::OAUTH_TOKEN,
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
        self::USERNAME,
        self::PASSWORD,
    ];

    const CONFIG_FLAGS = [
        self::COD_INTELLIGENCE,
        self::ONE_CLICK_CHECKOUT,
        self::ONE_CC_GA_ANALYTICS,
        self::ONE_CC_FB_ANALYTICS,
        self::ONE_CC_CAPTURE_BILLING_ADDRESS,
        self::ONE_CC_INTERNATIONAL_SHIPPING,
        self::ONE_CC_BUY_NOW_BUTTON,
        self::ONE_CC_AUTO_FETCH_COUPONS,
        self::ONE_CC_CAPTURE_GSTIN,
        self::ONE_CC_CAPTURE_ORDER_INSTRUCTIONS,
    ];

    const CONFIG_CUM_FEATURE_FLAGS = [
        self::ONE_CLICK_CHECKOUT,
        self::ONE_CC_GA_ANALYTICS,
        self::ONE_CC_FB_ANALYTICS
    ];

    const CONFIG_FLAGS_ACROSS_ALL_PLATFORMS = [
        self::COD_INTELLIGENCE,
        self::ONE_CC_CAPTURE_BILLING_ADDRESS,
        self::ONE_CC_INTERNATIONAL_SHIPPING,
        self::MANUAL_CONTROL_COD_ORDER,
        self::ONE_CC_CAPTURE_GSTIN,
        self::ONE_CC_CAPTURE_ORDER_INSTRUCTIONS,
    ];

    const INTERNAL_CONFIGS = [
        self::ONE_CC_ADDRESS_SYNC_OFF,
    ];

    const SHOPIFY_RESETTABLE_CONFIGS = [
        self::PLATFORM,
        self::SHOP_ID,
        self::MANUAL_CONTROL_COD_ORDER,
        self::DOMAIN_URL,
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


}
