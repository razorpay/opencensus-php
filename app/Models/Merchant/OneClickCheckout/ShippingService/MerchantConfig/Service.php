<?php

namespace RZP\Models\Merchant\OneClickCheckout\ShippingService\MerchantConfig;

use RZP\Http\Request\Requests;

class Service
{
    protected $app;

    const CREATE_MERCHANT_CONFIG                = 'create_merchant_config';
    const REMOVE_SHIPPING_PROVIDERS             = 'remove_shipping_providers';
    const UPDATE_BY_TYPE                        = 'update_by_type';
    const PATH                                  = 'path';

    // update merchant config attributes singleton class
    const SHIPPING_SERVICE_MERCHANT_CONFIG = 'shipping_service_merchant_config';

    const PARAMS = [
        self::CREATE_MERCHANT_CONFIG  =>   [
            self::PATH   => 'twirp/rzp.shipping.merchant_config.v1.MerchantConfigAPI/Create',
        ],
        self::REMOVE_SHIPPING_PROVIDERS  =>   [
            self::PATH   => 'twirp/rzp.shipping.merchant_config.v1.MerchantConfigAPI/RemoveShippingProviders',
        ],
        self::UPDATE_BY_TYPE => [
            self::PATH   => 'twirp/rzp.shipping.merchant_config.v1.MerchantConfigAPI/UpdateByType',
        ],
    ];

    public function __construct($app = null)
    {
        if ($app === null)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;
    }

    public function create($input)
    {

        $params = self::PARAMS[self::CREATE_MERCHANT_CONFIG];

        return $this->app['shipping_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    public function removeShippingProviders($merchantId)
    {
        $input = [];

        $input = $this->addMerchantDetails($input, $merchantId);

        $params = self::PARAMS[self::REMOVE_SHIPPING_PROVIDERS];

        return $this->app['shipping_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    public function updateByType($input)
    {
        $params = self::PARAMS[self::UPDATE_BY_TYPE];

        return $this->app['shipping_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }


    protected function addMerchantDetails($input, $merchantId)
    {

        $input['merchant_id'] = $merchantId;

        return $input;
    }
}
