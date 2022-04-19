<?php

namespace RZP\Models\Merchant\Merchant1ccConfig;

class Type {

    const SHIPPING_INFO_URL = 'shipping_info_url';
    const FETCH_COUPONS_URL = 'fetch_coupons_url';
    const APPLY_COUPON_URL  = 'apply_coupon_url';
    const PLATFORM          = 'platform';
    const COD_INTELLIGENCE = 'cod_intelligence';

    // supported platform types
    const NATIVE            = 'native';
    const WOOCOMMERCE       = 'woocommerce';
    const SHOPIFY           = 'shopify';
    const MAGENTO           = 'magento';

    //shipping_method_provider, eventually move to 1cc-shipping-service
    const SHIPPING_METHOD_PROVIDER = 'shipping_method_provider';
}
