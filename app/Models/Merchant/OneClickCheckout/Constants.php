<?php

namespace RZP\Models\Merchant\OneClickCheckout;

class Constants
{
    // for future use
    const PLATFORM          = 'platform';
    const SHIPPING_INFO_URL = 'shipping_info_url';
    const FETCH_COUPONS_URL = 'fetch_coupons_url';
    const APPLY_COUPON_URL  = 'apply_coupon_url';

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

    // common auth keys
    const API_KEY        = 'api_key';
    const API_SECRET     = 'api_secret';
    const OAUTH_TOKEN    = 'oauth_token'; // NOTE: Or ACCESS_TOKEN
    const REFRESH_TOKEN  = 'refresh_token';

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

    const ENCRYPTED_FIELDS = [
        self::API_SECRET,
        self::STOREFRONT_ACCESS_TOKEN,
        self::OAUTH_TOKEN,
    ];

    const CONFIG_FLAGS = [
        self::COD_INTELLIGENCE,
        self::ONE_CLICK_CHECKOUT,
        self::ONE_CC_GA_ANALYTICS,
        self::ONE_CC_FB_ANALYTICS,
        self::ONE_CC_CAPTURE_BILLING_ADDRESS,
        self::ONE_CC_INTERNATIONAL_SHIPPING,
        self::ONE_CC_BUY_NOW_BUTTON,
        self::ONE_CC_AUTO_FETCH_COUPONS
    ];

    const CONFIG_CUM_FEATURE_FLAGS = [
        self::ONE_CLICK_CHECKOUT,
        self::ONE_CC_GA_ANALYTICS,
        self::ONE_CC_FB_ANALYTICS
    ];

    const CONFIG_FLAGS_ACROSS_ALL_PLATFORMS = [
        self::COD_INTELLIGENCE,
        self::ONE_CC_CAPTURE_BILLING_ADDRESS,
        self::ONE_CC_INTERNATIONAL_SHIPPING
    ];

    const SHOPIFY_RESETTABLE_CONFIGS = [
        self::PLATFORM,
        self::SHOP_ID
    ];

    const NATIVE_RESETTABLE_CONFIGS = [
        self::PLATFORM,
        self::SHIPPING_INFO_URL,
        self::FETCH_COUPONS_URL,
        self::APPLY_COUPON_URL
    ];
}
