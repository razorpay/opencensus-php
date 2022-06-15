<?php

namespace RZP\Models\Merchant\OneClickCheckout\Config;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $nativeRules = [
        "shipping_info"                  => 'sometimes|url',
        "list_promotions"                => 'sometimes|url',
        "apply_promotion"                => 'sometimes|url',
        "cod_slabs"                      => 'sometimes|array',
        "shipping_slabs"                 => 'sometimes|array',
        "cod_intelligence"               => 'sometimes|boolean',
        'platform'                       => 'required|in:native,woocommerce,shopify,magento',
        "one_cc_auto_fetch_coupons"      => 'sometimes|boolean',
        "one_cc_international_shipping"  => 'sometimes|boolean',
        "one_cc_capture_billing_address" => 'sometimes|boolean',

    ];

    protected static $shopifyRules = [
        'shop_id'                        => 'required|string',
        "cod_intelligence"               => 'sometimes|boolean',
        "one_click_checkout"             => 'sometimes|boolean',
        "one_cc_ga_analytics"            => 'sometimes|boolean',
        "one_cc_fb_analytics"            => 'sometimes|boolean',
        "one_cc_buy_now_button"          => 'sometimes|boolean',
        "one_cc_auto_fetch_coupons"      => 'sometimes|boolean',
        "one_cc_international_shipping"  => 'sometimes|boolean',
        "one_cc_capture_billing_address" => 'sometimes|boolean'
    ];
}
