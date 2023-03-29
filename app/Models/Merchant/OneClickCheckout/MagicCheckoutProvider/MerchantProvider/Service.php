<?php

namespace RZP\Models\Merchant\OneClickCheckout\MagicCheckoutProvider\MerchantProvider;

use App;
use RZP\Http\Request\Requests;

class Service
{
    protected $app;

    const PATH                                  = 'v1/merchant';
    const SYNC_SHOPIFY_CRED                     = "credentials/shopify";

    public function __construct($app = null)
    {
        if ($app === null)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;
    }

    public function syncShopify1ccCredentials($merchantId)
    {
        $path = self::PATH . '/' .$merchantId. '/' . self::SYNC_SHOPIFY_CRED;

        return $this->app['magic_checkout_service_client']->sendRequest($path, null, Requests::GET);

    }

}
