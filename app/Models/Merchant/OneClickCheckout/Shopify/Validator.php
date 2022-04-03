<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use RZP\Base;
use RZP\Models\Merchant\OneClickCheckout\Constants as Constants;

class Validator extends Base\Validator
{
    const UPDATE_CHECKOUT = 'update_checkout';

    protected static $updateCheckoutRules = [
        Constants::ORDER_ID => 'required|string|size:20',
    ];
}
