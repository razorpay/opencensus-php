<?php

namespace RZP\Models\Merchant\Merchant1ccConfig;

class Type {

    const SHIPPING_INFO_URL                = 'shipping_info_url';
    const FETCH_COUPONS_URL                = 'fetch_coupons_url';
    const APPLY_COUPON_URL                 = 'apply_coupon_url';
    const PLATFORM                         = 'platform';
    const COD_INTELLIGENCE                 = 'cod_intelligence';
    const ONE_CLICK_CHECKOUT               = 'one_click_checkout';
    const ONE_CC_BUY_NOW_BUTTON            = 'one_cc_buy_now_button';
    const ONE_CC_AUTO_FETCH_COUPONS        = 'one_cc_auto_fetch_coupons';
    const ONE_CC_INTERNATIONAL_SHIPPING    = 'one_cc_international_shipping';
    const ONE_CC_CAPTURE_BILLING_ADDRESS   = 'one_cc_capture_billing_address';
    const ONE_CC_GA_ANALYTICS              = 'one_cc_ga_analytics';
    const ONE_CC_FB_ANALYTICS              = 'one_cc_fb_analytics';

    // supported platform types
    const NATIVE            = 'native';
    const WOOCOMMERCE       = 'woocommerce';
    const SHOPIFY           = 'shopify';
    const MAGENTO           = 'magento';

    //shipping_method_provider, eventually move to 1cc-shipping-service
    const SHIPPING_METHOD_PROVIDER = 'shipping_method_provider';
}
