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
}
