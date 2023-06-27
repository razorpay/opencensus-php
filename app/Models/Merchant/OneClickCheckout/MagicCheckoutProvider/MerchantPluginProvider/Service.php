<?php

namespace RZP\Models\Merchant\OneClickCheckout\MagicCheckoutProvider\MerchantPluginProvider;

use App;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;

class Service
{
    protected $app;

    const MERCHANT_ID = 'merchant_id';
    const SOURCE = 'source';
    const PATH     = 'v1/merchants/plugin_info';
    const FETCH = "fetch";

   

    public function __construct($app = null)
    {
        if ($app === null)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;
    }


    public function push1ccWoocPluginInfo($input)
    {
        $input[self::MERCHANT_ID] = $this->app['basicauth']->getMerchant()->getId();
        
        $this->app['trace']->info(
        TraceCode::WOOCOMMERCE_MERCHANT_PLUGIN_INFO_BODY,
        ['type' => 'merchant_plugin_info',
            'request_body'=> $input]);

        return $this->app['magic_checkout_service_client']->sendRequest(self::PATH, $input, Requests::POST);
      
       
    }
}
