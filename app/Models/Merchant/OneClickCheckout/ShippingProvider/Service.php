<?php

namespace RZP\Models\Merchant\OneClickCheckout\ShippingProvider;


use RZP\Http\Request\Requests;

class Service
{
    protected $app;

    const CREATE_SHIPPING_PROVIDER             = 'create_shipping_provider';
    const UPDATE_SHIPPING_PROVIDER             = 'update_shipping_provider';
    const LIST_SHIPPING_PROVIDER               = 'list_shipping_providers';
    const DELETE_SHIPPING_PROVIDER             = 'delete_shipping_providers';
    const CONNECT_SHIPPING_PROVIDER            = 'connect_shipping_provider';
    const PATH                                 = 'path';

    const PARAMS = [
        self::CREATE_SHIPPING_PROVIDER  =>   [
            self::PATH   => 'twirp/rzp.shipping.shipping_provider.v1.ShippingProviderAPI/Create',
        ],
        self::UPDATE_SHIPPING_PROVIDER  =>   [
            self::PATH   => 'twirp/rzp.shipping.shipping_provider.v1.ShippingProviderAPI/Update',
        ],
        self::DELETE_SHIPPING_PROVIDER => [
            self::PATH   => 'twirp/rzp.shipping.shipping_provider.v1.ShippingProviderAPI/Delete',
        ],
        self::LIST_SHIPPING_PROVIDER => [
            self::PATH   => 'twirp/rzp.shipping.shipping_provider.v1.ShippingProviderAPI/List',
        ],
        self::CONNECT_SHIPPING_PROVIDER => [
            self::PATH   => 'twirp/rzp.shipping.shipping_provider.v1.ShippingProviderAPI/Connect'
        ]
    ];

    public function __construct($app = null)
    {
        if ($app === null)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;
    }


    public function list($providerType, $merchantId)
    {
        $input = [
            'provider_type' => $providerType,
        ];

        $input = $this->addMerchantDetails($input, $merchantId);

        $params = self::PARAMS[self::LIST_SHIPPING_PROVIDER];

        $res = $this->app['shipping_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);

        $items = [];
        if (isset($res['shipping_providers']) === true)
        {
            $items = $res['shipping_providers'];
        }

        return [
            'entity' => 'collection',
            'count'  => count($items),
            'items'  => $items,
        ];
    }

    public function create($input, $merchantId)
    {

        $input = $this->addMerchantDetails($input, $merchantId);

        $params = self::PARAMS[self::CREATE_SHIPPING_PROVIDER];

        $response =  $this->app['shipping_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);

        $this->app['shipping_service_merchant_config']->disableShopifyAsShippingProvider([
            'merchant_ids' => [
                $merchantId,
            ]
        ]);

        return $response;
    }

    public function update($input, $merchantId)
    {
        $input = $this->addMerchantDetails($input, $merchantId);

        $params = self::PARAMS[self::UPDATE_SHIPPING_PROVIDER];

        return $this->app['shipping_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    public function delete($id, $merchantId)
    {
        $input = [
            'id' => $id
        ];

        $input = $this->addMerchantDetails($input, $merchantId);

        $params = self::PARAMS[self::DELETE_SHIPPING_PROVIDER];

        $response =  $this->app['shipping_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);

        $this->handleShopifyAssignment($merchantId);

        return $response;

    }

    public function connect($input)
    {
        $params = self::PARAMS[self::CONNECT_SHIPPING_PROVIDER];

        return $this->app['shipping_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    protected function addMerchantDetails($input, $merchantId)
    {

        $input['merchant_id'] = $merchantId;

        return $input;
    }

    protected function handleShopifyAssignment($merchantId): void
    {

        $listResponse = $this->app['shipping_provider_service']->list('', $merchantId);
        $assignShopify = true;

        foreach ($listResponse['items'] as $providers)
        {
            if ($providers['provider_type'] == 'shiprocket'
                || $providers['provider_type'] == 'delhivery')
            {
                $assignShopify = false;
                break;
            }
        }

        if ($assignShopify === true)
        {
            $this->app['shipping_service_merchant_config']->assignShopifyAsShippingProvider([
                'merchant_ids' => [
                    $merchantId,
                ],
                'type' => 'create'
            ]);
        }

    }
}
