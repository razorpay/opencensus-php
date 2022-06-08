<?php

namespace RZP\Models\Merchant\ShippingInfo;

use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Country;
use RZP\Http\Request\Requests;
use RZP\Models\Merchant\Slab;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\Validator;
use RZP\Models\Merchant\OneClickCheckout\Shopify;
use RZP\Models\Merchant\Merchant1ccConfig;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\OneClickCheckout\ShippingMethodProvider\Type;
use RZP\Models\Merchant\OneClickCheckout\ShippingMethodProvider\Constants;

class Service extends Base\Service
{

    const SECOND    = 1;
    const MINUTE    = 60 * self::SECOND;
    const HOUR      = 60 * self::MINUTE;

    const SHIPPING_INFO_ID               = 'id';
    const SHIPPING_INFO_ADDRESSES        = 'addresses';
    const SHIPPING_INFO_ADDRESS          = 'address';
    const SHIPPING_INFO_CACHE_KEY_PREFIX = 'SHIPPING_INFO_';
    const SHIPPING_INFO_CACHE_VALIDITY   = 30 * self::MINUTE; // 30 minutes


    /**
     * Get Merchant Serviceability and COD Serviceability for a given Address
     *
     * @param array $input
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\ServerErrorException
     * @throws \Throwable
     */
    public function getShippingInfo(array $input): array
    {
        if($this->merchant === null or $this->merchant->isFeatureEnabled(FeatureConstants::ONE_CLICK_CHECKOUT) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $decodedResponse = [];

        $ex = '';

        $dimensions = array_merge($input,
            [
                'merchant_id' => $this->merchant->getId(),
                'mode' => $this->mode,
            ]);

        try {

            $this->trace->count(Metric::MERCHANT_SHIPPING_INFO_CHECK_CALL_COUNT, $dimensions);

            $serviceabilityCheckStartTime = millitime();

            if(!isset($input[self::SHIPPING_INFO_ADDRESSES]) || !isset($input['order_id']))
            {
                $this->trace->count(Metric::MERCHANT_SHIPPING_INFO_CALL_INVALID_REQUEST_COUNT, $dimensions);

                $ex = new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_SERVICEABILITY_INVALID_INPUT
                );

                throw $ex;
            }

            $mockResponse = $input['mock_response'] ?? null;

            unset($input['mock_response']);

            $orderId = $input['order_id'];

            $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

            $orderMeta = $this->repo->order_meta->findByPublicOrderIdAndType($orderId, FeatureConstants::ONE_CLICK_CHECKOUT);

            if($orderMeta === null)
            {
                $this->trace->count(Metric::MERCHANT_SHIPPING_INFO_CALL_INVALID_REQUEST_COUNT,
                    array_merge($dimensions,
                        [
                            'order_meta' => $orderMeta
                        ])
                );
                $ex = new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_1CC_ORDER);
                throw $ex;
            }

            $merchantOrderId = null;

            try
            {
                $merchantOrderId = $order->getReceipt();
            }
            catch (Throwable $e)
            {
                $this->trace->count(Metric::MERCHANT_SHIPPING_INFO_CALL_INVALID_REQUEST_COUNT,
                    array_merge($dimensions,
                        [
                            'error' => $e
                        ])
                );
                $ex = new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_1CC_ORDER);
                throw $ex;
            }

            if(is_null($merchantOrderId))
            {
                $this->trace->count(Metric::MERCHANT_SHIPPING_INFO_CALL_INVALID_REQUEST_COUNT,
                    array_merge($dimensions, [
                        'merchant_order_id' => $merchantOrderId
                    ])
                );
                $ex = new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_SERVICEABILITY_INVALID_INPUT
                );
                throw $ex;
            }

            $input['order_id'] = $merchantOrderId;

            // Leaving the bulk contract for backward compatibility
            $addresses = $input[self::SHIPPING_INFO_ADDRESSES];
            if (count($addresses) !== 1)
            {
                $this->trace->count(Metric::MERCHANT_SHIPPING_INFO_CALL_INVALID_REQUEST_COUNT, $dimensions);
                $ex = new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_SERVICEABILITY_INVALID_INPUT
                );
                throw $ex;
            }

            $address = $addresses[0];
            (new Validator())->setStrictFalse()->validateInput("shippingInfoRequest", $address);

            $address = $this->getPincodeAndState($address);

            $cachedResponse = $this->getShippingInfoFromCache($orderId, $address);

            if (!empty($cachedResponse))
            {
                $this->trace->debug(TraceCode::MERCHANT_SHIPPING_INFO_NO_UNCACHED_ADDRESS, ["order_id" => $orderId]);

                $this->traceResponseTime(Metric::MERCHANT_SHIPPING_INFO_CHECK_TIME_MILLIS, $serviceabilityCheckStartTime, ['merchant_id' => $this->merchant->getId()]);

                /*
                * // Will be enabled once multiple shipping is launched
               $address['shipping_methods'] = $cachedResponse['shipping_methods'];
               */

                $decodedResponse = [self::SHIPPING_INFO_ADDRESSES => [$cachedResponse]];

                return [
                    self::SHIPPING_INFO_ADDRESSES => [$cachedResponse],
                ];
            }

            $platformConfig = $this->merchant->getMerchantPlatformConfig();

            // shopify configs take priority over all Rzp serviceability features
            if ($platformConfig !== null and $platformConfig->getValue() === Merchant1ccConfig\Type::SHOPIFY)
            {
                $this->trace->count(Metric::MERCHANT_SHIPPING_INFO_SHOPIFY_CALL_COUNT, array_merge(
                    $dimensions,
                    [
                        'platform' => $platformConfig->getValue()
                    ]
                ));
                $decodedResponse = (new Shopify\Service)->getShippingInfo([
                    'order_id' => $order->toArrayPublic()['notes']['storefront_id'],
                    'address' => array_merge($address, [self::SHIPPING_INFO_ID => 0]),
                ]);
            }
            else
            {
                $shippingMethodProviderConfig = $this->merchant->getShippingMethodProvider();
                if ($shippingMethodProviderConfig !== null)
                {
                    $shippingMethodProviderConfigJson = $shippingMethodProviderConfig->getValueJson();
                    $shippingProviderType = $shippingMethodProviderConfigJson[Constants::PROVIDER_TYPE] ?? Type::SHIPROCKET;
                    $this->trace->count(Metric::SHIPPING_SERVICE_CALL_COUNT, array_merge(
                        $dimensions,
                        [
                            'provider_type' => $shippingProviderType
                        ]
                    ));
                    switch ($shippingProviderType)
                    {
                        case Type::DEMO:
                        case Type::RAZORPAY:
                            $decodedResponse = $this->getShippingMethods(
                                $shippingMethodProviderConfigJson,
                                $address,
                                $orderId,
                                $orderMeta->getValue()['line_items_total'],
                                $order->toArrayPublic()['notes']);
                            break;
                        default:
                            $decodedResponse = $this->getShippingInfoForShippingMethodProvider($shippingMethodProviderConfig,
                                $address, $orderId);
                            break;
                    }
                }
                else
                {

                    $serviceabilityUrlConfig = $this->merchant->getShippingInfoUrlConfig();

                    if ($serviceabilityUrlConfig === null)
                    {
                        $this->trace->count(Metric::MERCHANT_SHIPPING_INFO_CALL_INVALID_REQUEST_COUNT, array_merge(
                            $dimensions,
                            [
                                'shipping_info_url' => $serviceabilityUrlConfig
                            ]
                        ));
                        $ex = new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_MERCHANT_SERVICEABILITY_URL_NOT_CONFIGURED);
                        throw $ex;
                    }

                    $serviceabilityUrl = $serviceabilityUrlConfig->getValue();

                    try
                    {
                        // Sending array for backward compatibility (bulk api)
                        $response = $this->sendMerchantShippingInfoRequest(
                            $merchantOrderId,
                            [array_merge($address, [self::SHIPPING_INFO_ID => 0])],
                            $serviceabilityUrl,
                            $mockResponse);

                        $decodedResponse = json_decode($response->body, true);
                        $decodedResponse = $decodedResponse[self::SHIPPING_INFO_ADDRESSES][0];
                        if (isset($decodedResponse[self::SHIPPING_INFO_ID]))
                        {
                            unset($decodedResponse[self::SHIPPING_INFO_ID]);
                        }
                    }
                    catch(Throwable $exception)
                    {
                        // Swallowing the exception to allow the request to go through in case merchant call fails
                        $this->trace->error(TraceCode::ERROR_EXCEPTION, ['error' => $exception->getMessage()]);

                        $decodedResponse = [];
                    }

                    if (json_last_error() !== JSON_ERROR_NONE || !isset($response) || $response->status_code !== 200)
                    {
                        $this->trace->count(Metric::MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_FAILURE_COUNT,
                            array_merge($dimensions,
                                ['errorcode' => ErrorCode::SERVER_ERROR_MERCHANT_SERVICEABILITY_EXTERNAL_CALL_EXCEPTION]
                            )
                        );
                        $decodedResponse = [];
                    }

                    try
                    {
                        (new Validator())->setStrictFalse()->validateInput("addressShippingInfoResponse", $decodedResponse);
                    }
                    catch (Throwable $e)
                    {
                        $this->trace->count(Metric::MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_FAILURE_COUNT,
                            ['errorcode' => ErrorCode::SERVER_ERROR_MERCHANT_SERVICEABILITY_EXTERNAL_CALL_EXCEPTION]);
                        $decodedResponse = [];
                    }
                }
            }

            // Backwards compatibility for merchant serviceability url/shopify that does not return methods
            $decodedResponse = $this->convertOldFormatToShippingMethods($decodedResponse);

            $address = array_merge($decodedResponse, $address);
            foreach ($address['shipping_methods'] as &$method)
            {
                if (isset($method['cod_fee']) === false)
                {
                    $method['cod_fee'] = $this->getFeeFromSlab(
                        $orderMeta->getValue()['line_items_total'],
                        Slab\Type::COD_SLAB);
                }

                if (isset($method['shipping_fee']) === false)
                {
                    $method['shipping_fee'] = $this->getFeeFromSlab(
                        $orderMeta->getValue()['line_items_total'],
                        Slab\Type::SHIPPING_SLAB);
                }

                if (isset($method['serviceable']) === false)
                {
                    $method['serviceable'] = true;
                }

                if (isset($method['cod']) === false)
                {
                    $method['cod'] = false;
                }
            }

            $platform = [];

            if ($platformConfig !== null)
            {
                $platform = ['platform' => $platformConfig->getValue()];
            }

            $this->traceResponseTime(
                Metric::MERCHANT_SHIPPING_INFO_CHECK_TIME_MILLIS,
                $serviceabilityCheckStartTime,
                $platform
            );

            // TODO: Remove this once the api contract change is finalized
            $address = $this->convertShippingMethodsToOldFormat($address);

            // Calculating COD Serviceability based on slabs if required.

            $merchantCodSlabServiceabilityConfig = $this->repo->merchant_1cc_configs->findByMerchantAndConfigType(
                $this->merchant->getId(),
                'cod_slab_serviceability'
            );

            if ($merchantCodSlabServiceabilityConfig !== null
                and $merchantCodSlabServiceabilityConfig->getValue() === "1")
            {
                $address['cod'] = $this->getCodServiceabilityFromSlabs($orderMeta->getValue()['line_items_total']);
            }

            $this->cacheMerchantShippingInfo($orderId, $address);

            return [self::SHIPPING_INFO_ADDRESSES => [$address]];

        } finally {
            if (empty($ex) === true){
                $this->trace->info(TraceCode::MERCHANT_ADDRESS_SHIPPING_INFO_REQUEST,
                    array_merge($dimensions,
                        [
                            'response' => $decodedResponse,
                            'exception' => $ex
                        ])
                );
            }else {
                $this->trace->error(TraceCode::MERCHANT_ADDRESS_SHIPPING_INFO_ERROR,
                    array_merge($dimensions,
                        [
                            'response' => $decodedResponse,
                            'exception' => $ex->getTrace()
                        ])
                );
            }
        }
    }

    protected function getCodServiceabilityFromSlabs(int $amount): bool
    {
        $slabsEntity = $this->merchant->slab(Slab\Type::COD_SERVICEABILITY_SLAB);

        if ($slabsEntity === null)
        {
            return false;
        }
        $slabs = $slabsEntity->getSlab();
        return $this->getServiceabilityFromSlabs($amount, $slabs);
    }

    protected function getServiceabilityFromSlabs(int $amount, array $slabs)
    {
        $serviceability = false;
        foreach ($slabs as $slab)
        {
            if ($slab['amount'] > $amount)
            {
                break;
            }

            $serviceability = $slab['serviceability'];
        }
        return $serviceability;
    }

    protected function convertOldFormatToShippingMethods(array $address): array
    {
        if (isset($address['shipping_methods']) === true)
        {
            return $address;
        }

        $shippingInfo = [
            'shipping_methods' => [
                [
                    'name' => 'default',
                    'description' => 'default',
                ]
            ]
        ];
        foreach (['shipping_fee',
                  'serviceable',
                  'cod',
                  'cod_fee',] as $key)
        {
            if (isset($address[$key]) === true)
            {
                $shippingInfo['shipping_methods'][0][$key] = $address[$key];
            }
        }
        return $shippingInfo;
    }

    protected function convertShippingMethodsToOldFormat(array $address): array
    {
        if (isset($address['shipping_methods']) === false)
        {
            return $address;
        }

        foreach ([
                     'shipping_fee',
                     'serviceable',
                     'cod',
                     'cod_fee',
                 ] as $key)
        {
            $address[$key] = $address['shipping_methods'][0][$key];
        }

        unset($address['shipping_methods']);

        return $address;
    }

    protected function getShippingInfoForShippingMethodProvider($shippingMethodProviderEntity, $address, $orderId): array
    {

        $shippingMethodProvider = $shippingMethodProviderEntity->getValueJson();
        $merchantId = $this->merchant->getId();
        $input = [
            'order_id' => $orderId,
            'address' => $address
        ];
        $shippingInfo = $this->app['shipping_method_provider_service']
            ->getShippingInfoForAddress($shippingMethodProvider, $input, $merchantId);

        return array_merge($input['address'], $shippingInfo);
    }

    protected function getShippingMethods($shippingMethodProviderConfig, $address, $orderId, $lineItemsTotal, $notes): array
    {
        $address['country_code'] = $address['country'];
        unset($address['country']);

        $address['zip_code'] = $address['zipcode'];
        unset($address['zipcode']);

        $shippingInfo = $this->app['shipping_methods_service']->evaluate(
            $shippingMethodProviderConfig['shipping_provider_id'],
            $address,
            $lineItemsTotal,
            $orderId,
            $this->merchant->getId(),
            $notes);
        $address['country'] = $address['country_code'];
        unset($address['country_code']);
        $address['zipcode'] = $address['zip_code'];
        unset($address['zip_code']);

        $keys = [
            'shipping_fee',
            'serviceable',
            'cod',
            'cod_fee',
            'name',
            'description',
        ];

        // Filling empty values as protobuf omits empty fields and converting strings to ints due to protobuf serialization
        for ($i = 0; $i < count($shippingInfo['shipping_methods']); $i++)
        {
            foreach ($keys as $key)
            {
                if (isset($shippingInfo['shipping_methods'][$i][$key]) === false)
                {
                    switch ($key)
                    {
                        case 'name':
                            $shippingInfo['shipping_methods'][$i][$key] = 'default';
                            break;
                        case 'description':
                            $shippingInfo['shipping_methods'][$i][$key] = '';
                            break;
                        case 'cod_fee':
                        case 'shipping_fee':
                            $shippingInfo['shipping_methods'][$i][$key] = 0;
                            break;
                        case 'cod':
                        case 'serviceable':
                            $shippingInfo['shipping_methods'][$i][$key] = false;
                            break;
                    }
                }
                elseif ($key === 'shipping_fee' or $key === 'cod_fee')
                {
                    $shippingInfo['shipping_methods'][$i][$key] = (int)$shippingInfo['shipping_methods'][$i][$key];
                }
            }
        }
        return array_merge($address, $shippingInfo);
    }

    protected function getFeeFromSlab(int $amount, string $type): int
    {
        $slabsEntity = $this->merchant->slab($type);

        if ($slabsEntity === null)
        {
            return 0;
        }
        $slabs = $slabsEntity->getSlab();
        return $this->getFeeByAmountFromSlabs($amount, $slabs);
    }

    protected function getFeeByAmountFromSlabs(int $amount, array $slabs)
    {
        $fee = 0;

        foreach ($slabs as $slab)
        {
            if ($slab['amount'] > $amount)
            {
                break;
            }

            $fee = $slab['fee'];
        }

        return $fee;
    }

    public function getShippingInfoFromCache($orderId, $address){
        return $this->app['cache']->get(
            $this->getShippingInfoCacheKey($orderId, $address));
    }

    /**
     * @param string $merchantOrderId
     * @param array $addresses
     * @param string $serviceabilityUrl
     * @param array|null $mockResponse
     * @return mixed
     * @throws Exception\ServerErrorException
     */
    protected function sendMerchantShippingInfoRequest(string $merchantOrderId, array $addresses, string $serviceabilityUrl, array $mockResponse = null)
    {
        if (!is_null($mockResponse))
        {
            return $this->sendRequest(null, $mockResponse);
        }
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
        $request = array(
            'url' => $serviceabilityUrl,
            'method' => Requests::POST,
            'headers' => $headers,
            'content' => json_encode(['order_id' => $merchantOrderId, 'addresses' => $addresses])
        );
        $this->trace->count(Metric::MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_COUNT);
        $externalRequeststartTime = millitime();
        try
        {
            $response = $this->sendRequest($request);
            $this->traceResponseTime(Metric::MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_TIME_MILLIS, $externalRequeststartTime);
            $this->trace->info(TraceCode::MERCHANT_ADDRESS_SHIPPING_INFO_RESPONSE, (array)$response);
            return $response;
        }
        catch (Exception\ServerErrorException $e)
        {
            $this->trace->count(Metric::MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_FAILURE_COUNT,
                ['errorcode' => $e->getCode()]);
            throw $e;
        }
    }

    /**
     * @throws Exception\ServerErrorException
     */
    private function sendRequest($request, $mockResponse = null)
    {
        if((getenv('APP_ENV') === 'testing') and
            ($mockResponse !== null))
        {
            $mockResponseObj = new \stdClass();

            $mockResponseObj->body = json_encode($mockResponse['body']);
            $mockResponseObj->status_code = $mockResponse['status_code'] ?? 200;

            return $mockResponseObj;
        }

        $method = $request['method'];

        try
        {
            $response = Requests::$method(
                $request['url'],
                $request['headers'],
                $request['content']);
        }
        catch (Throwable $e)
        {
            throw new Exception\ServerErrorException(
                'Error while calling URL',
                ErrorCode::SERVER_ERROR,
                null,
                $e
            );
        }
        return $response;
    }

    /**
     * Standard group-by aggregate function.
     * Groups by calling keyfunc on each item.
     * keyfunc(item1) => [item1, item2...]
     */
    private static function group_by(callable $keyfunc, array $input): array {
        $ret = array();
        foreach($input as $value) {
            $key = $keyfunc($value);
            if(!isset($ret[$key])){
                $ret[$key] = array();
            }
            array_push($ret[$key], $value);
        }
        return $ret;
    }

    /**
     * Standard assoc by key function.
     * Creates a map from pairfunc(item) => item.
     */
    private static function array_map_assoc(callable $pairfunc, array $a): array
    {
        if(empty($a) === true)
        {
            return $a;
        }
        return array_merge(...array_map($pairfunc, $a));
    }

    /**
     * @param $addresses
     * @throws \Throwable
     */
    protected function validateShippingInfoRequest($addresses): void
    {
        try
        {
            $validator = (new Validator)->setStrictFalse();
            array_walk($addresses,
                function ($address) use ($validator) {
                    $validator->validateInput("shippingInfoRequest", $address);
                });
        }
        catch (\Throwable $e)
        {
            $this->trace->count(Metric::MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_INVALID_REQUEST_COUNT);
            throw $e;
        }
    }

    /**
     * @param $decodedResponse
     * @throws \Throwable
     */
    private function validateDecodedMerchantShippingInfoResponse($decodedResponse): void
    {
        try
        {
            $validator = (new Validator);
            $validator->setStrictFalse();
            array_walk($decodedResponse['addresses'],
                function ($response) use ($validator) {
                    $validator->validateInput('addressShippingInfoResponse', $response);
                });
        }
        catch (\Throwable $e)
        {
            $this->trace->count(Metric::MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_INVALID_RESPONSE_COUNT);
            throw new Exception\ServerErrorException(
                "Invalid Merchant Response",
                ErrorCode::SERVER_ERROR_MERCHANT_SERVICEABILITY_EXTERNAL_CALL_EXCEPTION
            );
        }
    }

    /**
     * @param $orderId
     * @param $address
     */
    protected function cacheMerchantShippingInfo($orderId, $address): void
    {
        $this->app['cache']->put(
            $this->getShippingInfoCacheKey($orderId, $address),
            $address,
            self::SHIPPING_INFO_CACHE_VALIDITY);
    }

    /**
     * @param $orderId
     * @param $address
     * @return string
     */
    private function getShippingInfoCacheKey($orderId, $address): string
    {
        $zipcode = $address['zipcode'] ?? "";

        return self::SHIPPING_INFO_CACHE_KEY_PREFIX
            . $this->merchant->getId()
            . "_"
            . $orderId
            . "_"
            . $zipcode
            . "_"
            . $address['country'];
    }

    /**
     * @param $address
     * @return mixed
     */
    protected function getPincodeAndState($address)
    {
        try
        {
            $response = $this->app['pincodesearch']->fetchCityAndStateFromPincode($address['zipcode'], true, true, $address['country']);
        }
        catch (Throwable $e)
        {
            $this->trace->error(TraceCode::PINCODE_SEARCH_ERROR,
                ['error' => $e->getMessage()]);
            $response = ['city' => '', 'state' => '', 'state_code' => ''];
        }

        $address['city'] = $response['city'];

        $address['state'] = $response['state'];

        $address['state_code'] = $response['state_code'];
        return $address;
    }

    protected function traceResponseTime(string $metric, int $startTime, $extraDimensions = [])
    {
        $duration = millitime() - $startTime;

        $dimensions = array_merge(
            $extraDimensions,
            [
                'merchant_id' => $this->merchant->getId(),
            ]);

        $this->trace->histogram($metric, $duration, $dimensions);
    }
}
