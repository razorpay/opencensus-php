<?php

namespace RZP\Models\Merchant\OneClickCheckout\RtoRecommendation;

use RZP\Models\Merchant\OneClickCheckout\Constants;
use RZP\Models\Order\Entity;
use RZP\Base;

class Validator extends Base\Validator
{
    protected static $actionHandlerRules = [
        Constants::ACTION              => 'required|in:approve,cancel,hold',
        Constants::PLATFORM            => 'required|in:native,shopify,woocommerce',
        Entity::ID                     => 'required|string|max:20',
        Constants::MERCHANT_ID         => 'required|string',
        Constants::MODE                => 'required|in:test,live',
    ];
}
