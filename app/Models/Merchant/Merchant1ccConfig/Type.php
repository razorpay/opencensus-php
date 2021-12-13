<?php

namespace RZP\Models\Merchant\Merchant1ccConfig;

class Type {

    const SHIPPING_INFO_URL = 'shipping_info_url';
    const FETCH_COUPONS_URL = 'fetch_coupons_url';
    const APPLY_COUPON_URL  = 'apply_coupon_url';
    const PLATFORM          = 'platform';

    // supported platform types
    const NATIVE            = 'native';
    const WOOCOMMERCE       = 'woocommerce';
    const SHOPIFY           = 'shopify';
    const MAGENTO           = 'magento';
}
