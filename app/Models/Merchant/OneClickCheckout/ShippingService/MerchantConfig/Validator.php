<?php

namespace RZP\Models\Merchant\OneClickCheckout\ShippingService\MerchantConfig;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Validator extends Base\Validator
{
    protected static $shopifyAssignmentRules = [
        'merchant_ids'       => 'required|array|min:1|max:50',
        'type'               => 'required|string|in:create,switch',
    ];
    protected static $disableShopifyRules = [
        'merchant_ids'       => 'required|array|min:1|max:50',
    ];
}
