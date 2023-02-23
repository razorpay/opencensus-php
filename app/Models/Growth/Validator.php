<?php

namespace RZP\Models\Growth;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $sendPricingBundleEmailRules = [
        Constants::TYPE        => 'required|string|in:'.Constants::PAYMENT_FAILURE . ',' . Constants::PAYMENT_SUCCESS . ',' . Constants::WELCOME,
        Constants::DATA        => 'sometimes|array',
        Constants::MERCHANT_ID => 'required|string|size:14',
        Constants::PACKAGE_NAME => 'sometimes|string|max:30'
    ];
}
