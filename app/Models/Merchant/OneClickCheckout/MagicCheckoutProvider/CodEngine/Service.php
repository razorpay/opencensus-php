<?php

namespace RZP\Models\Merchant\OneClickCheckout\MagicCheckoutProvider\CodEngine;

use App;
use RZP\Http\Request\Requests;

class Service
{
    protected $app;

    const PATH     = 'v1/shipping/cod';
    const Evaluate = "evaluate";

    public function __construct($app = null)
    {
        if ($app === null)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;
    }

    public function evaluate($input)
    {
        $path = self::PATH . '/' . self::Evaluate;

        return $this->app['magic_checkout_service_client']->sendRequest($path, $input, Requests::POST);
    }

}
