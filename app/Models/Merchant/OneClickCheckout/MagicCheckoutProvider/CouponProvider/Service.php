<?php

namespace RZP\Models\Merchant\OneClickCheckout\MagicCheckoutProvider\CouponProvider;

use App;
use RZP\Http\Request\Requests;

class Service
{
    protected $app;

    const PATH                                  = 'v1/coupons';
    const APPLY_COUPON                          = "apply";

    public function __construct($app = null)
    {
        if ($app === null)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;
    }

    public function applyCoupon($input)
    {
        $path = self::PATH . '/' . self::APPLY_COUPON;

        $body = $this->app['magic_checkout_service_client']->sendRequest($path, $input, Requests::POST);

        if (empty($body["failure_code"]) === true)
        {
            return ['status_code' => 200, 'data' => ['promotions' => [$body]]];
        }
        return ['status_code' => 422, 'data' => $body];
    }
}
