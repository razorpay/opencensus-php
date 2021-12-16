<?php

namespace RZP\Models\Merchant\OneClickCheckout;

class Constants {

    const PLATFORM          = 'platform';
    const SHIPPING_INFO_URL = 'shipping_info_url';
    const FETCH_COUPONS_URL = 'fetch_coupons_url';
    const APPLY_COUPON_URL  = 'apply_coupon_url';

    // supported platform types
    const NATIVE            = 'native';
    const WOOCOMMERCE       = 'woocommerce';
    const SHOPIFY           = 'shopify';
    const MAGENTO           = 'magento';

    /**
     * Types of credentials supported
     */
     // common
     const API_KEY          = 'api_key';
     const API_SECRET       = 'api_secret';
     const OAUTH_TOKEN      = 'oauth_token';
     const REFRESH_TOKEN    = 'refresh_token';

     // shopify specific
     const X_SHOPIFY_ACCESS_TOKEN             = 'X-Shopify-Access-Token';
     const X_SHOPIFY_STOREFRONT_ACCESS_TOKEN  = 'X-Shopify-Storefront-Access-Token';
     const STOREFRONT_ACCESS_TOKEN            = 'storefront_access_token';
     const SHOP_ID                            = 'shop_id';

     const SHOPIFY_AUTH = [
         self::API_KEY,
         self::API_SECRET,
         self::STOREFRONT_ACCESS_TOKEN,
         self::SHOP_ID,
     ];

     const SHOPIFY_AUTH_ENCRYPT = [
         self::API_SECRET,
         self::STOREFRONT_ACCESS_TOKEN,
     ];

     const ENCRYPTED_FIELDS = [
         self::API_SECRET,
         self::STOREFRONT_ACCESS_TOKEN,
     ];
}
