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

class Service extends Base\Service
{

    const SECOND    = 1;
    const MINUTE    = 60 * self::SECOND;
    const HOUR      = 60 * self::MINUTE;

    const SHIPPING_INFO_ID                              = 'id';
    const SHIPPING_INFO_ADDRESSES                       = 'addresses';
    const SHIPPING_INFO_CACHE_KEY_PREFIX                = 'SHIPPING_INFO_';
    const SHIPPING_INFO_CACHE_VALIDITY = 30 * self::MINUTE; // 30 minutes


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

        $this->trace->info(TraceCode::MERCHANT_ADDRESS_SHIPPING_INFO_REQUEST, $input);

        $this->trace->count(Metric::MERCHANT_SHIPPING_INFO_CHECK_CALL_COUNT);

        $serviceabilityCheckStartTime = millitime();

        if(!isset($input[self::SHIPPING_INFO_ADDRESSES]) || !isset($input['order_id']))
        {
            $this->trace->count(Metric::MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_INVALID_REQUEST_COUNT);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_SERVICEABILITY_INVALID_INPUT
            );
        }

        $mockResponse = $input['mock_response'] ?? null;

        unset($input['mock_response']);

        $orderId = $input['order_id'];

        $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

        $orderMeta = $this->repo->order_meta->findByPublicOrderIdAndType($orderId, FeatureConstants::ONE_CLICK_CHECKOUT);

        if($orderMeta === null)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_1CC_ORDER);
        }

        $merchantOrderId = null;

        try
        {
            $merchantOrderId = $order->getReceipt();
        }
        catch (Throwable $e)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_1CC_ORDER);
        }

        if(is_null($merchantOrderId))
        {
            $this->trace->count(Metric::MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_INVALID_REQUEST_COUNT);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_SERVICEABILITY_INVALID_INPUT
            );
        }

        $input['order_id'] = $merchantOrderId;

        $addresses = $input[self::SHIPPING_INFO_ADDRESSES];

        $this->validateShippingInfoRequest($addresses);

        $addresses = $this->getPincodeAndState($addresses);

        $groupedAddresses = self::group_by(
            function (&$address) use ($orderId)
            {
                $address['cod'] = false;

                $cachedResponse = $this->getShippingInfoFromCache($orderId, $address);

                if (!empty($cachedResponse))
                {
                    $address['serviceable'] = $cachedResponse['serviceable'];

                    $address['cod'] = $cachedResponse['cod'];

                    $address['cod_fee'] = $cachedResponse['cod_fee'] ?? null;

                    $address['shipping_fee'] = $cachedResponse['shipping_fee'];

                    return 'cached';
                }
                return 'noncached';
            },
            $addresses);

        $cachedAddresses = $groupedAddresses['cached'] ?? [];

        $nonCachedAddresses = $groupedAddresses['noncached'] ?? [];

        if(empty($nonCachedAddresses) === true)
        {
            $this->trace->debug(TraceCode::MERCHANT_SHIPPING_INFO_NO_UNCACHED_ADDRESS, ["order_id" => $orderId]);

            $this->traceResponseTime(Metric::MERCHANT_SHIPPING_INFO_CHECK_TIME_MILLIS, $serviceabilityCheckStartTime);

            return [self::SHIPPING_INFO_ADDRESSES => array_merge($nonCachedAddresses, $cachedAddresses)];
        }

        array_walk($nonCachedAddresses,
            function (&$address, $id)
            {
                $address[self::SHIPPING_INFO_ID] = $id;
            });

        $platformConfig = $this->merchant->getMerchantPlatformConfig();

        if ($platformConfig !== null and $platformConfig->getValue() === Merchant1ccConfig\Type::SHOPIFY)
        {
            $decodedResponse = (new Shopify\Service)->getShippingInfo([
                'order_id' => $order->toArrayPublic()['notes']['storefront_id'],
                'addresses' => $nonCachedAddresses
            ]);
        }
        else
        {
            $shippingMethodProviderConfig = $this->merchant->getShippingMethodProvider();
            if ($shippingMethodProviderConfig !== null)
            {

                $decodedResponse = $this->getShippingInfoForShippingMethodProvider($shippingMethodProviderConfig,
                    $nonCachedAddresses, $orderId);

            }
            else
            {

                $serviceabilityUrlConfig = $this->merchant->getShippingInfoUrlConfig();

                if ($serviceabilityUrlConfig === null)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_MERCHANT_SERVICEABILITY_URL_NOT_CONFIGURED);
                }

                $serviceabilityUrl = $serviceabilityUrlConfig->getValue();

                try
                {
                    $response = $this->sendMerchantShippingInfoRequest($merchantOrderId, $nonCachedAddresses, $serviceabilityUrl, $mockResponse);

                    $decodedResponse = json_decode($response->body, true);
                }
                catch(Throwable $exception)
                {
                    // Swallowing the exception to allow the request to go through in case merchant call fails
                    $this->trace->info($exception->getMessage());

                    $decodedResponse = ['addresses' => []];
                }

                if (json_last_error() !== JSON_ERROR_NONE || $response->status_code !== 200)
                {
                    $this->trace->count(Metric::MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_FAILURE_COUNT,
                        ['errorcode' => ErrorCode::SERVER_ERROR_MERCHANT_SERVICEABILITY_EXTERNAL_CALL_EXCEPTION]);

                    $decodedResponse = ['addresses' => []];
                }
            }
        }

        try
        {
            $this->validateDecodedMerchantShippingInfoResponse($decodedResponse);
        }
        catch (Throwable $e)
        {
            $this->trace->count(Metric::MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_FAILURE_COUNT,
                ['errorcode' => ErrorCode::SERVER_ERROR_MERCHANT_SERVICEABILITY_EXTERNAL_CALL_EXCEPTION]);

            $decodedResponse = ['addresses' => []];
        }

        $serviceability = self::array_map_assoc(
            function ($address) {
                return [$address[self::SHIPPING_INFO_ID] => $address];
            },
            $decodedResponse['addresses']);

        array_walk($nonCachedAddresses,
            function (&$address) use ($orderMeta, $orderId, $serviceability)
            {
                $id = $address[self::SHIPPING_INFO_ID];

                if (isset($serviceability[$id]))
                {
                    $address['serviceable'] = $serviceability[$id]['serviceable'];

                    $address['cod'] = $serviceability[$id]['cod'];

                    if(isset($serviceability[$id]['cod_fee']) === true and $serviceability[$id]['cod_fee'] !== null)
                    {
                        $address['cod_fee'] = $serviceability[$id]['cod_fee'];
                    }

                    if (isset($serviceability[$id]['shipping_fee']) === true and $serviceability[$id]['shipping_fee'] !== null)
                    {
                        $address['shipping_fee'] = $serviceability[$id]['shipping_fee'];
                    }
                }

                if (isset($address['cod_fee']) === false)
                {
                    $address['cod_fee'] = $this->getFeeFromSlab(
                        $orderMeta->getValue()['line_items_total'],
                        Slab\Type::COD_SLAB);
                }

                if (isset($address['shipping_fee']) === false)
                {
                    $address['shipping_fee'] = $this->getFeeFromSlab(
                        $orderMeta->getValue()['line_items_total'],
                        Slab\Type::SHIPPING_SLAB);
                }

                if (isset($address['serviceable']) === false)
                {
                    $address['serviceable'] = true;
                }

                $this->cacheMerchantShippingInfo($orderId, $address);

                unset($address[self::SHIPPING_INFO_ID]);
            });

        $dimensions = [];

        if ($platformConfig !== null)
        {
            $dimensions = ['platform' => $platformConfig->getValue()];
        }

        $this->traceResponseTime(
            Metric::MERCHANT_SHIPPING_INFO_CHECK_TIME_MILLIS,
            $serviceabilityCheckStartTime,
            $dimensions
        );

        return [self::SHIPPING_INFO_ADDRESSES => array_merge($nonCachedAddresses, $cachedAddresses)];
    }

    protected function getShippingInfoForShippingMethodProvider($shippingMethodProviderEntity, $addresses, $orderId): array
    {
        if (count($addresses) > 1)
        {
            return ['addresses' => []];
        }

        $shippingMethodProvider = $shippingMethodProviderEntity->getValueJson();
        $merchantId = $this->merchant->getId();
        $input = [
            'order_id' => $orderId,
            'address' => $addresses[0]
        ];
        $shippingInfo = $this->app['shipping_method_provider_service']
            ->getShippingInfoForAddress($shippingMethodProvider, $input, $merchantId);

        $res = array_merge($input['address'], $shippingInfo);
        return ['addresses'=> [$res]];
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
     * @param $addresses
     * @return mixed
     */
    protected function getPincodeAndState($addresses)
    {
        array_walk(
            $addresses,
            function (&$address)
            {
                try
                {
                    $response = $this->app['pincodesearch']->fetchCityAndStateFromPincode($address['zipcode'], true, false, $address['country']);
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
            });
        return $addresses;
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
