<?php

namespace RZP\Models\Merchant\OneClickCheckout\ShippingService\MerchantConfig;

use GuzzleHttp\Exception\GuzzleException;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Http\Request\Requests;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\OneClickCheckout\AuthConfig;
use RZP\Trace\TraceCode;
use GuzzleHttp\Client;

class Service
{
    protected $app;

    protected $validator;

    const CREATE_MERCHANT_CONFIG                = 'create_merchant_config';
    const REMOVE_SHIPPING_PROVIDERS             = 'remove_shipping_providers';
    const UPDATE_BY_TYPE                        = 'update_by_type';
    const LIST_MERCHANT_CONFIG                  = 'list_merchant_config';
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
        self::LIST_MERCHANT_CONFIG  =>   [
            self::PATH   => 'twirp/rzp.shipping.merchant_config.v1.MerchantConfigAPI/List',
        ]
    ];

    const OrderSyncConfig           =   "order_sync_config";
	const FulfillmentEventConfig    =   "fulfillment_event_config";

    public function __construct($app = null)
    {
        if ($app === null)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;

        $this->validator = new Validator();
    }

    public function create($input)
    {

        $params = self::PARAMS[self::CREATE_MERCHANT_CONFIG];

        return $this->app['shipping_service_client']->sendRequest($params[self::PATH], $input, Requests::POST);
    }

    public function list($input)
    {

        $params = self::PARAMS[self::LIST_MERCHANT_CONFIG];

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

    /**
     * @throws BadRequestException
     */
    public function assignShopifyAsShippingProvider($input)
    {
        $this->validator->validateInput('shopify_assignment',$input);

        $authConfig = new AuthConfig\Core();

        $response = [];
        foreach ($input['merchant_ids'] as $merchant_id)
        {
            if ($input['type'] == 'switch')
            {
                try
                {
                    $this->removeShippingProviders($merchant_id);
                }
                catch (\Exception $e)
                {
                    $this->app['trace']->traceException($e, Trace::ERROR, TraceCode::SHOPIFY_FULFILLMENT_UPDATE_WEBHOOK_ASSIGNMENT_FAILED);

                    $this->app['trace']->count(Metric::SHOPIFY_FULFILLMENT_UPDATE_WEBHOOK_ASSIGNMENT_FAILED_COUNT, [
                            'type'  =>  'remove_shipping_provider'
                    ]);

                    $response[$merchant_id] = $e->getMessage();
                    continue;
                }
            }

            try
            {
                $configs = $authConfig->getShopify1ccConfig($merchant_id);
                if ($configs == null || !isset($configs['shop_id']) || !isset($configs['oauth_token']))
                {
                    $response[$merchant_id] = 'SHOPIFY_CONFIGS_NOT_FOUND';

                    continue;
                }

                $listMerchantConfigRequest = [
                    'merchant_id'  =>  $merchant_id,
                ];
                $orderSyncConfig = null;
                $fulfillmentEventConfig = null;

                $listMerchantConfigResponse = $this->list($listMerchantConfigRequest);

                foreach ($listMerchantConfigResponse['merchant_configs'] as $merchantConfig) {
                    if($merchantConfig['type'] === self::OrderSyncConfig)
                    {
                        $orderSyncConfig = $merchantConfig;
                    }
                    if($merchantConfig['type'] === self::FulfillmentEventConfig)
                    {
                        $fulfillmentEventConfig = $merchantConfig;
                    }
                }

                if ($input['type'] !== 'switch' && isset($orderSyncConfig) &&
                    isset($orderSyncConfig['enabled_shipping_providers']) &&
                    count($orderSyncConfig['enabled_shipping_providers']) > 0)
                {

                    $response[$merchant_id] = 'MERCHANT CONNECTED LOGISTICS PARTNER';
                    continue;
                }

                $this->sendWebhookCreateRequest($configs['shop_id'], $configs['oauth_token']);

                $merchantConfigCreateRequest = array(
                    'merchant_id' => $merchant_id,
                    'type' => 'fulfillment_event_config',
                    'fulfillment_event_config' => array(
                        'enabled_platform' => 'shopify'
                    )
                );
                if (isset($fulfillmentEventConfig))
                {
                    $this->updateByType($merchantConfigCreateRequest);
                }
                else
                {
                    $this->create($merchantConfigCreateRequest);
                }

                $response[$merchant_id] = 'SUCCESS';
            }
            catch (\Exception $e)
            {
                $this->app['trace']->traceException($e, Trace::ERROR, TraceCode::SHOPIFY_FULFILLMENT_UPDATE_WEBHOOK_ASSIGNMENT_FAILED);

                $this->app['trace']->count(Metric::SHOPIFY_FULFILLMENT_UPDATE_WEBHOOK_ASSIGNMENT_FAILED_COUNT, [
                        'type'  =>  'webhook_creation'
                ]);

                $response[$merchant_id] = $e->getMessage();
            }
        }

        return $response;
    }

    /**
     * @throws BadRequestException
     */
    public function disableShopifyAsShippingProvider($input)
    {
        $this->validator->validateInput('disable_shopify',$input);

        $authConfig = new AuthConfig\Core();

        $response = [];

        foreach ($input['merchant_ids'] as $merchant_id)
        {
            try
            {
                $configs = $authConfig->getShopify1ccConfig($merchant_id);

                if ($configs == null || !isset($configs['shop_id']) || !isset($configs['oauth_token']))
                {
                    $response[$merchant_id] = 'SHOPIFY_CONFIGS_NOT_FOUND';

                    continue;
                }

                $this->sendWebhookDisableRequest($configs['shop_id'], $configs['oauth_token']);

                $listMerchantConfigRequest = [
                    'merchant_id'  =>  $merchant_id,
                ];

                $fulfillmentEventConfig = null;

                $listMerchantConfigResponse = $this->list($listMerchantConfigRequest);

                foreach ($listMerchantConfigResponse['merchant_configs'] as $merchantConfig)
                {
                    if($merchantConfig['type'] === self::FulfillmentEventConfig)
                    {
                        $fulfillmentEventConfig = $merchantConfig;
                    }
                }
                if(isset($fulfillmentEventConfig))
                {

                    $merchantConfigCreateRequest = array(
                        'merchant_id' => $merchant_id,
                        'type' => 'fulfillment_event_config',
                        'fulfillment_event_config' => array(
                            'enabled_platform' => ''
                        )
                    );

                    $this->updateByType($merchantConfigCreateRequest);
                }

                $this->app['shipping_provider_service']->connect(['merchant_id' => $merchant_id]);

                $response[$merchant_id] = 'SUCCESS';
            }
            catch (\Exception $e)
            {
                $this->app['trace']->traceException($e, Trace::ERROR, TraceCode::SHOPIFY_FULFILLMENT_UPDATE_WEBHOOK_DISABLE_FAILED);

                $this->app['trace']->count(Metric::SHOPIFY_FULFILLMENT_UPDATE_WEBHOOK_DISABLE_FAILED_COUNT);

                $response[$merchant_id] = 'FAILED';
            }
            catch (GuzzleException $e)
            {
                $this->app['trace']->traceException($e, Trace::ERROR, TraceCode::SHOPIFY_FULFILLMENT_UPDATE_WEBHOOK_DISABLE_FAILED);

                $this->app['trace']->count(Metric::SHOPIFY_FULFILLMENT_UPDATE_WEBHOOK_DISABLE_FAILED_COUNT);

                $response[$merchant_id] = 'FAILED';
            }
        }

        return $response;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function sendWebhookCreateRequest(string $storeId, string $token)
    {
        $client = new Client();

        $headers = [
            'X-Shopify-Access-Token' => $token,
            'Content-Type' => 'application/json'
        ];

        $body = '{
            "webhook": {
                "topic": "fulfillments/update",
                "address": "https://api.razorpay.com/v1/1cc/process_webhooks/shopify",
                "format": "json",
                "fields": [
                    "order_id",
                    "shipment_status",
                    "tracking_number",
                    "tracking_numbers",
                    "tracking_company",
                    "tracking_url",
                    "name"
                ]
            }
        }';

        $response = $client->request('POST',
            'https://'.$storeId.'.myshopify.com/admin/api/2022-01/webhooks.json',
            [
                'headers' => $headers,
                'body' => $body
            ]);
        return json_decode($response->getBody(), true);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    protected function sendWebhookDisableRequest(string $storeId, string $token)
    {
        $client = new Client();

        $headers = [
            'X-Shopify-Access-Token' => $token,
            'Content-Type' => 'application/json'
        ];
        $response = $client->request('GET',
            'https://'.$storeId.'.myshopify.com/admin/api/2022-01/webhooks.json',
            [
                'headers' => $headers,
            ]);
        $responseArray = json_decode($response->getBody(), true);
        $webhookId = '';
        foreach ($responseArray['webhooks'] as $webhook)
        {
            if  ($webhook['topic'] === 'fulfillments/update')
            {
                $webhookId = $webhook['id'];
                break;
            }
        }
        if ($webhookId === '')
        {
            $this->app['trace']->info(TraceCode::FULFILLMENT_UPDATE_WEBHOOK_NOT_CONFIGURED,
                [
                    'store_id'  =>  $storeId,
                    'webhook'   =>  'fulfillments/update',
                ]
            );
            return ;
        }

        $response = $client->request('DELETE',
            'https://'.$storeId.'.myshopify.com/admin/api/2022-01/webhooks/'.$webhookId.'.json',
            [
                'headers' => $headers,
            ]);
        return json_decode($response->getBody(), true);
    }
}
