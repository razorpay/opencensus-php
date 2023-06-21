<?php

namespace RZP\Models\Merchant\OneClickCheckout\MagicCheckoutProvider\PrepayCODProvider;

use App;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;

class Service
{
    protected $app;

    const MERCHANT_ID = 'merchant_id';
    const ORDER_ID = 'order_id';
    const MODE = 'mode';

    public function __construct($app = null)
    {
        if ($app === null)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;
    }

    public function convert1ccPrepayCODOrders($input)
    {
        try
        {
            if(!isset($input[self::MERCHANT_ID]))
            {
                $input[self::MERCHANT_ID] = $this->app['basicauth']->getMerchant()->getId();
            }

            $input[self::ORDER_ID] = substr($input[self::ORDER_ID],6);

            if(!isset($input[self::MODE]))
            {
                $input[self::MODE] = $this->app['rzp.mode'];
            }

            $this->app['magic_checkout_service_client']->sendRequest('v1/payment_links/create', $input, "POST");
        } catch (\Throwable $e) {
            $this->app['trace']->error(
                TraceCode::ONE_CC_PREPAY_COD_CREATE_PL_FAILED,
                [
                    'input' => $input,
                    'message' => $e->getMessage()
                ]);
            throw $e;
        }
    }

}
