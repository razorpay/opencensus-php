<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use RZP\Base;
use RZP\Models\Merchant\OneClickCheckout\Constants as Constants;

class Validator extends Base\Validator
{
    const UPDATE_CHECKOUT = 'update_checkout';
    const CREATE_CHECKOUT = 'create_checkout';
    const COMPLETE_CHECKOUT = 'complete_checkout';

    protected static $createCheckoutRules = [
      Constants::ORDER_ID => 'required|string|size:20',
      Constants::ORDER_ID => 'required|string|size:20',
    ];

    protected static $updateCheckoutRules = [
        Constants::ORDER_ID => 'required|string|size:20',
    ];

    protected static $completeCheckoutRules = [
      'razorpay_order_id'   => 'required|string|size:20',
      'razorpay_payment_id' => 'required|string|size:18',
    ];
}
