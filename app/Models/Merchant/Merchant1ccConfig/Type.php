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
    const ONE_CC_HANDLE_DIGITAL_PRODUCT    = 'one_cc_handle_digital_product';
    const ONE_CC_GUPSHUP_CREDENTIALS       = 'one_cc_gupshup_credentials';
    const ONE_CC_ENABLE_GUPSHUP            = 'one_cc_enable_gupshup';

    const ONE_CC_GIFT_CARD                 = 'one_cc_gift_card';
    const ONE_CC_GIFT_CARD_RESTRICT_COUPON = 'one_cc_gift_card_restrict_coupon';
    const ONE_CC_BUY_GIFT_CARD             = 'one_cc_buy_gift_card';
    const ONE_CC_GIFT_CARD_PIN             = 'one_cc_gift_card_pin';
    const ONE_CC_MULTIPLE_GIFT_CARD        = 'one_cc_multiple_gift_card';
    const ONE_CC_GIFT_CARD_COD_RESTRICT    = 'one_cc_gift_card_cod_restrict';
    const DOMAIN_URL                       = 'domain_url';

    const COUPON_CONFIG                    = 'coupon_config';
    const ORDER_STATUS_UPDATE_URL          = 'order_status_update_url';
    const MANUAL_CONTROL_COD_ORDER         = 'manual_control_cod_order';
    const ONE_CC_PREPAY_COD_CONVERSION     = 'one_cc_prepay_cod_conversion';
    const API_KEY                          = 'api_key';
    const API_SECRET                       = 'api_secret';
    const USERNAME                         = 'username';
    const PASSWORD                         = 'password';

    const ONE_CC_CAPTURE_GSTIN             = 'one_cc_capture_gstin';
    const ONE_CC_CAPTURE_ORDER_INSTRUCTIONS = 'one_cc_capture_order_instructions';

    // supported platform types
    const NATIVE            = 'native';
    const WOOCOMMERCE       = 'woocommerce';
    const SHOPIFY           = 'shopify';
    const MAGENTO           = 'magento';

    //shipping_method_provider, eventually move to 1cc-shipping-service
    const SHIPPING_METHOD_PROVIDER = 'shipping_method_provider';

    const SHOPIFY_SHIPPING_OVERRIDE = 'shopify_shipping_override';
    const SHIPPING_VARIANT_STRATEGY = 'shipping_variant_strategy';
    const SHIPPING_VARIANTS = 'shipping_variants';

    //cod-engine configs
    const COD_ENGINE                        = 'cod_engine';
    const COD_ENGINE_TYPE                   = 'cod_engine_type';
}
