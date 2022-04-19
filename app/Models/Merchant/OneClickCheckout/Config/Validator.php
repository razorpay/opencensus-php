<?php

namespace RZP\Models\Merchant\OneClickCheckout\Config;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $nativeRules = [
        "shipping_info"    => 'sometimes|url',
        "list_promotions"  => 'sometimes|url',
        "apply_promotion"  => 'sometimes|url',
        "cod_slabs"        => 'sometimes|array',
        "shipping_slabs"   => 'sometimes|array',
        "cod_intelligence" => 'sometimes|boolean',
        'platform'         => 'required|in:native,woocommerce,shopify,magento',
    ];

    protected static $shopifyRules = [
        'shop_id' => 'required|string',
    ];
}
