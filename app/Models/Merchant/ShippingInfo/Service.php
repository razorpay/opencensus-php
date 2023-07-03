<?php

namespace RZP\Models\Merchant\ShippingInfo;

use RZP\Http\Controllers\MagicCheckoutController;
use RZP\Models\Merchant\OneClickCheckout\MagicCheckoutService\Client;
use RZP\Models\Merchant\OneClickCheckout\MagicCheckoutService\Client as MagicCheckoutServiceClient;
use RZP\Models\Merchant\OneClickCheckout\Shopify\StateMap;
use RZP\Models\Order\Entity;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\OneClickCheckout\MigrationUtils\SplitzExperimentEvaluator;
use RZP\Models\Merchant\OneClickCheckout\ShippingMethodProvider\FeeRule;
use RZP\Models\Order\OrderMeta\Order1cc\Fields;
use RZP\Models\Order\ProductType;
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
use RZP\Models\Merchant\OneClickCheckout\DomainUtils;
use RZP\Models\Merchant\OneClickCheckout\ShippingMethodProvider\Type;
use RZP\Models\Merchant\OneClickCheckout\ShippingMethodProvider\Constants;
use RZP\Models\Merchant\ShippingInfo\Constants as ShippingInfoConstants;

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
    const SERVICEABLE = 'serviceable';
    const COD =  'cod';
    const DISABLE_SHIPPING_CACHE_RESET = 'disable_shipping_cache_reset'; //shipping cache fix backward compatibility
    const DEFAULT_SHIPPING_VARIANT = "__default";

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
            /**
             * For payment_store product, by defauly we want magic checkout to be used
             * explicitly setting feature flag for each merchant is not scalable,
             * hence we added additional checks for payment_store product specifically.
             */
            $forNocodeApps = $this->getNocodeAppsResponseIfApplicable($input);

            if (empty($forNocodeApps) === false)
            {
                return $forNocodeApps;
            }

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $decodedResponse = [];

        $ex = '';

        $dimensions = [
            'mode' => $this->mode,
        ];

        $this->trace->count(Metric::MERCHANT_SHIPPING_INFO_CHECK_CALL_COUNT, $dimensions);

        try {
            $serviceabilityCheckStartTime = millitime();

            if(!isset($input[self::SHIPPING_INFO_ADDRESSES]) || !isset($input['order_id']))
            {
                $ex = new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_SERVICEABILITY_INVALID_INPUT
                );

                throw $ex;
            }

            $mockResponse = $input['mock_response'] ?? null;

            unset($input['mock_response']);

            $orderId = $input['order_id'];

            $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

            /**
             * For payment_store product, by defauly we want magic checkout to be used
             * payment page merchants can also use payment_store product, which means
             * merchants may already have the feature flag enabled. In such cases we want
             * the behaviour to be the same, hence we added additional checks for nocodeapps specifically.
             */
            if (ProductType::IsForNocodeApps($order->product_type))
            {
                $nocodeResp = $this->getNocodeAppsResponseFor1ccOrder($order, $input);

                if (empty($nocodeResp) === false)
                {
                    return $nocodeResp;
                }
            }

            $orderMeta = array_first($order->orderMetas ?? [], function ($orderMeta)
            {
                return $orderMeta->getType() === \RZP\Models\Order\OrderMeta\Type::ONE_CLICK_CHECKOUT;
            });

            if($orderMeta === null)
            {
                $ex = new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_1CC_ORDER);
                throw $ex;
            }
            $orderMetaArray = $orderMeta->getValue();

            // ShippingVariant is required for merchants with multiple shipping configurations with the same mid
            $shippingVariant = $this->getShippingVariant($orderMetaArray);

            $merchantOrderId = null;
            $productType = $order->getProductType();
            if ($productType == null || $productType !== ProductType::PAYMENT_PAGE)
            {
            try
            {
                $merchantOrderId = $order->getReceipt();
            }
            catch (Throwable $e)
            {
                $ex = new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_1CC_ORDER);
                throw $ex;
            }

            if(is_null($merchantOrderId))
            {
                $ex = new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_SERVICEABILITY_INVALID_INPUT
                );
                throw $ex;
            }

            $input['order_id'] = $merchantOrderId;
            }
            // Leaving the bulk contract for backward compatibility
            $addresses = $input[self::SHIPPING_INFO_ADDRESSES];
            if (count($addresses) !== 1)
            {
                $ex = new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_SERVICEABILITY_INVALID_INPUT
                );
                throw $ex;
            }

            $address = $addresses[0];
            (new Validator())->setStrictFalse()->validateInput("shippingInfoRequest", $address);

            (new Validator())->validateStateCode($address);

            $address = $this->getCountryAndStateBasedOnZipcode($address);

            $cachedResponse = $this->getShippingInfoFromCache($orderId, $address, $order->getAmount());

            $isDigitalProduct = false;

            if (!empty($cachedResponse))
            {
                $this->trace->debug(TraceCode::MERCHANT_SHIPPING_INFO_NO_UNCACHED_ADDRESS, ["order_id" => $orderId]);

                $this->traceResponseTime(Metric::MERCHANT_SHIPPING_INFO_CHECK_TIME_MILLIS, $serviceabilityCheckStartTime, $dimensions);

                /*
                * // Will be enabled once multiple shipping is launched
               $address['shipping_methods'] = $cachedResponse['shipping_methods'];
               */

                $decodedResponse = [self::SHIPPING_INFO_ADDRESSES => [$cachedResponse]];
                // Not ideal nomenclature but we are doing this as the code is too large to extract "source".
                $dimensions['platform'] = 'cache';
                $this->recordShippingInfoResp($cachedResponse, $dimensions);
                return [
                    self::SHIPPING_INFO_ADDRESSES => [$cachedResponse],
                ];
            }
            //Temporary fix for PP Shipping Fee(Once Shipping Provider is built for PP this can be removed)
            if ($productType != null && $productType === ProductType::PAYMENT_PAGE)
            {
                $dimensions['platform'] = 'others';
                $productId = $order->getProductId();
                $paymentPage = $this->repo->payment_link->findByIdAndMerchant($productId, $this->merchant);
                $settings = $paymentPage->getSettings()->toArray();
                $shippingFeeRule = $settings[Constants::SHIPPING_FEE_RULE] ?? null;
                $address[Fields::SHIPPING_FEE] = $this->getFees($shippingFeeRule, $orderMeta->getValue()[Fields::LINE_ITEMS_TOTAL]);
                $address[self::SERVICEABLE] = true;
                $address[self::COD] = false;
                $address[Fields::COD_FEE] = 0;
            }
            else
            {
            $platformConfig = $this->merchant->getMerchantPlatformConfig();
            if ($platformConfig !== null)
            {
                $dimensions = array_merge($dimensions, ['platform' => $platformConfig->getValue()]);
            }
            $shippingMethodProviderConfig = $this->merchant->getShippingMethodProvider();
            $shopifyShippingOverride = (new Merchant1ccConfig\Core())->isShopifyShippingOverrideSet($this->merchant->getId());

            // shopify configs take priority over all Rzp serviceability features
            // The current conditions only allow shopify merchants to have fallback configuration
            // shopifyShippingOverride allows shopify merchants to use rzp shipping platform
            if ($platformConfig !== null
                and $platformConfig->getValue() === Merchant1ccConfig\Type::SHOPIFY
                and $shopifyShippingOverride === false
            )
            {
                $this->trace->count(Metric::MERCHANT_SHIPPING_INFO_SHOPIFY_CALL_COUNT, $dimensions);
                $decodedResponse = (new Shopify\Service)->getShippingInfo([
                    'order_id' => $order->toArrayPublic()['notes']['storefront_id'],
                    'address' => array_merge($address, [self::SHIPPING_INFO_ID => 0]),
                ]);
                $isDigitalProduct = $decodedResponse['is_digital_product'];
                unset($decodedResponse['is_digital_product']);

                if (empty($decodedResponse['use_fallback']) === false) {
                    unset($decodedResponse['use_fallback']);
                    if ($shippingMethodProviderConfig !== null) {
                        $decodedResponse = $this->shippingProviderOldFlow(
                            $shippingMethodProviderConfig,
                            $orderId,
                            $order,
                            $orderMeta,
                            $address,
                            $decodedResponse,
                            $merchantOrderId,
                            $mockResponse,
                            $dimensions,
                            $shippingVariant
                        );
                    }
                }
            }
            else
            {
                $payload = [
                    'id'            => UniqueIdEntity::generateUniqueId(),
                    'experiment_id' => $this->app['config']->get('app.1cc_shipping_info_migration_splitz_experiment_id'),
                    'request_data'  => json_encode(
                        [
                            'merchant_id' =>  $this->merchant->getId(),
                        ]),
                ];
                $evaluationResult = (new SplitzExperimentEvaluator())->evaluateExperiment($payload, true, 'var_on','', $dimensions, TraceCode::SHIPPING_MIGRATION_SPLITZ_ERROR);
                $this->trace->info(TraceCode::SHIPPING_MIGRATION_SPLITZ_RESPONSE,
                    array_merge($dimensions,
                        [
                            'splitz_evaluation_result' => $evaluationResult,
                        ])
                );
                if (empty($evaluationResult) === false &&
                    empty($evaluationResult['experiment_enabled']) === false &&
                    $evaluationResult['experiment_enabled'] === true)
                {
                    try {
                        $decodedResponse = (new Providers())->shippingProviderMigrationFlow(
                            $shippingMethodProviderConfig,
                            $address,
                            $orderId,
                            $orderMetaArray['line_items_total'],
                            $order->toArrayPublic()['notes'],
                            $merchantOrderId,
                            $shippingVariant
                        );
                        $this->trace->info(TraceCode::SHIPPING_MIGRATION_GET_API_RESPONSE,
                            [
                                'shipping_response' => $decodedResponse,
                            ]);
                    }
                    catch (Throwable $e)
                    {
                        $ex = $e;
                        throw $ex;
                    }
                }
                else
                {
                    try
                    {
                        // Shipping Info URL based flow. Used by native and WooCommerce merchants
                        $decodedResponse = $this->shippingProviderOldFlow(
                            $shippingMethodProviderConfig,
                            $orderId,
                            $order,
                            $orderMeta,
                            $address,
                            $decodedResponse,
                            $merchantOrderId,
                            $mockResponse,
                            $dimensions,
                            $shippingVariant
                        );
                    }
                    catch (Throwable $e)
                    {
                        $ex = $e;
                        throw $ex;
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

            $this->traceResponseTime(
                Metric::MERCHANT_SHIPPING_INFO_CHECK_TIME_MILLIS,
                $serviceabilityCheckStartTime,
                $dimensions
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

            }

            $configs = $this->repo->merchant_1cc_configs->findByMerchantAndConfigArray(
                $this->merchant->getId(),
                [Merchant1ccConfig\Type::COD_ENGINE, Merchant1ccConfig\Type::COD_ENGINE_TYPE]
            );

            $codEngineConfigs = array();
            foreach ($configs as $config) {
                $codEngineConfigs[$config->getConfig()] = $config->getValue();
            }
            // It will be executed if merchant has opted for magic-cod-engine
            if ($codEngineConfigs[Merchant1ccConfig\Type::COD_ENGINE] === '1')
            {
                $rzpOrderId = $order->getPublicId();
                $orderAmount = $orderMetaArray['line_items_total'];
                $orderAmountInRupee = $orderAmount/pow(10,2);
                $roundOrderAmount = round($orderAmountInRupee)*100;
                $products = [];
                foreach ($orderMetaArray['line_items'] as $lineItems){
                    $product = array();
                    $product['id'] = $lineItems['product_id'];
                    array_push($products, $product);
                }

                $customerInfo = array();
                $customerInfo['email'] = $orderMetaArray['customer_details']['email'];
                $customerInfo['phone'] = $orderMetaArray['customer_details']['contact'];
                $customerInfo['ip'] = $orderMetaArray['customer_details']['ip'];

                $inputOrder = [
                    'id'            => $rzpOrderId,
                    'amount'        => $roundOrderAmount,
                    'products'      => $products
                ];
                // cod engine uses shopify locations codes , override google location with shopify
                $stateCode = $stateCodeFromName = (new StateMap)->getPincodeMappedStateCode($address['zipcode']);

                if ($stateCode === null)
                {
                    $stateCode = (new StateMap)->getShopifyStateCode($address);

                    $stateCodeFromName = (new StateMap)->getShopifyStateCodeFromName($address);
                }
                $location = [
                    'zipcode'      => $address['zipcode'],
                    'state_code'   => strtoupper($stateCode ?? $stateCodeFromName),
                    'country_code' => strtoupper($address['country'])
                ];
                $codEngineEvaluateRequest = [
                    'merchant_id'   => $this->merchant->getMerchantId(),
                    'type'          => $codEngineConfigs[Merchant1ccConfig\Type::COD_ENGINE_TYPE],
                    'order'         => $inputOrder,
                    'location'      => $location,
                    'customer_info' => $customerInfo,
                ];

                if ($isDigitalProduct === false)
                {
                    // default values in case of failures.
                    $isCodEligible = false;
                    $codFee = 0;
                    try
                    {
                        $res = $this->app['magic_checkout_cod_engine_service']->evaluate($codEngineEvaluateRequest);
                        $isCodEligible = $res['cod'];
                        $codFee = $res['cod_fee'];
                    }
                    catch(\Exception $ex){
                        $this->trace->count(
                            Metric::MAGIC_COD_ENGINE_EVALUATE_API_ERROR_COUNT,
                            array_merge($dimensions, ['code' => $ex->getCode()])
                        );

                        $this->trace->error(TraceCode::MAGIC_COD_ENGINE_EVALUATE_CALL_ERROR,
                            [
                                'code'        => $ex->getCode(),
                                'message'     => $ex->getMessage(),
                                'merchant_id' => $this->merchant->getMerchantId()
                            ]
                        );
                    }
                    $this->trace->info(TraceCode::MAGIC_COD_ENGINE_EVALUATE_CALL_SUCCESS,
                        [
                            'response' => $res,
                        ]
                    );
                    $address['cod'] = $isCodEligible;
                    $address['cod_fee'] = $codFee;
                    // set cod fee for all shipping methods to support multiple shipping if feature flag is enabled.
                    if($this->merchant->isFeatureEnabled(FeatureConstants::ONE_CC_SHOPIFY_MULTIPLE_SHIPPING))
                    {
                        foreach ($address['shipping_methods'] as &$method)
                        {
                            $method['cod'] = $isCodEligible;
                            $method['cod_fee'] = $codFee;
                        }
                    }
                }
            }

            $this->cacheMerchantShippingInfo($orderId, $address, $order->getAmount());
            $this->recordShippingInfoResp($address, $dimensions);
            return [self::SHIPPING_INFO_ADDRESSES => [$address]];

        }
        catch (\Throwable $e)
        {
            $ex = $e;
            throw $e;
        }
        finally {
            if (empty($ex) === true){
                $this->trace->info(TraceCode::MERCHANT_ADDRESS_SHIPPING_INFO_REQUEST,
                    array_merge($dimensions,
                        [
                            'response' => $decodedResponse,
                            'exception' => $ex
                        ])
                );
            }else {
                $internalErrorCode = "";
                if (($ex instanceof Exception\BaseException) === true) {
                    $internalErrorCode = $ex->getError()->getInternalErrorCode();
                }
                $this->trace->count(Metric::MERCHANT_SHIPPING_INFO_CALL_INVALID_REQUEST_COUNT,
                    array_merge(
                        $dimensions,
                        ['internal_error_code' => $internalErrorCode]
                    )
                );
                $this->trace->error(TraceCode::MERCHANT_ADDRESS_SHIPPING_INFO_ERROR,
                    array_merge(
                        $dimensions,
                        [
                            'response' => $decodedResponse,
                            'internal_error_code' => $internalErrorCode,
                            'exception' => $ex->getTrace(),
                        ])
                );
            }
        }
    }

    protected function getShippingVariant($orderMetaArray): ?string
    {
        $mid = $this->merchant->getId();
        $shippingVariantStrategy = (new Merchant1ccConfig\Core())->getShippingVariantStrategy($mid);
        if (empty($shippingVariantStrategy) === true)
        {
            return null;
        }


        $shippingVariants = (new Merchant1ccConfig\Core())->getShippingVariants($mid) ?? [];

        if (empty($shippingVariants) === true)
        {
            return null;
        }

        // Create shippingVariantsDict to check if the products are matching.
        // If they are not, mark them as the default variant
        $shippingVariantsDict = [];
        foreach ($shippingVariants as $variant)
        {
            $shippingVariantsDict[$variant['name']] = 1;
        }

        // For now, there's only 1 strategy.
        // Product based strategy gives priority to the first variant in the array
        switch ($shippingVariantStrategy)
        {
            case Merchant1ccConfig\Constants::SHIPPING_VARIANT_STRATEGY_PRODUCT_TYPE:
                $productTypes = [];
                foreach ($orderMetaArray[Fields::LINE_ITEMS] as $item)
                {
                    $productType = $item[Fields::LINE_ITEM_TYPE] ?? '';
                    if($shippingVariantsDict[$productType] === 1)
                    {
                        $productTypes[$productType] = 1;
                    }
                    else
                    {
                        $productTypes[self::DEFAULT_SHIPPING_VARIANT] = 1;
                    }
                }
                foreach ($shippingVariants as $shippingVariant) {
                    if (isset($productTypes[$shippingVariant['name']]) === false)
                    {
                        continue;
                    }
                    return $shippingVariant['variant'] ?? null;
                }
        }

        return null;
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
                    'id' => 'default',
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
        //Unset the default shipping method if its present
        $shippingMethods = $address['shipping_methods'][0];
        if ($shippingMethods['name'] === 'default' &&
            $shippingMethods['id'] === 'default' &&
            $shippingMethods['description'] === 'default')
        {
            unset($address['shipping_methods']);
        }
        return $address;
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

    public function getShippingInfoFromCache($orderId, $address, $orderAmount)
    {
        //gets the shipping info for the order_amount
        $newKeyCachedResponse = $this->app['cache']->get(
            $this->getShippingInfoCacheKey($orderId, $address, $orderAmount));

        if (!empty($newKeyCachedResponse))
        {
            return $newKeyCachedResponse;
        }
        //if shipping info not found for new key it checks the disable_shipping_cache_reset
        // flag is enabled, then it will get the shipping info for the zipcode(old key)
        //This is required for backward compatibility of shipping_cache_fix
        $variant = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            self::DISABLE_SHIPPING_CACHE_RESET,
            $this->mode
        );
        if (empty($variant) === false && strtolower($variant) === 'on')
        {
            return $this->app['cache']->get(
                $this->getShippingInfoOldCacheKey($orderId, $address));
        }
    }

    protected function getFeeFromSlabs(int $amount, $slabs)
    {
        $fee = 0;
        foreach ($slabs as $slab)
        {
            if ($slab['lte'] >= $amount && $slab['gte'] < $amount)
            {
                $fee = $slab['fee'];
            }
        }
        return $fee;
    }

    /**
     * @param string $merchantOrderId
     * @param array $addresses
     * @param string $serviceabilityUrl
     * @param array|null $mockResponse
     * @return mixed
     * @throws Exception\ServerErrorException
     */
    protected function sendMerchantShippingInfoRequest(string $razorpayOrderId, string $merchantOrderId, array $addresses, string $serviceabilityUrl, array $mockResponse = null, array $dimensions = [])
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
            'content' => json_encode(['order_id' => $merchantOrderId, 'addresses' => $addresses, 'razorpay_order_id' => $razorpayOrderId])
        );
        $this->trace->count(Metric::MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_COUNT, $dimensions);
        $externalRequeststartTime = millitime();
        try
        {
            $response = $this->sendRequest($request);
            $this->traceResponseTime(Metric::MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_TIME_MILLIS, $externalRequeststartTime, $dimensions);
            $this->trace->info(TraceCode::MERCHANT_ADDRESS_SHIPPING_INFO_RESPONSE, (array)$response);
            return $response;
        }
        catch (Exception\ServerErrorException $e)
        {
            $this->trace->count(Metric::MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_FAILURE_COUNT,
                array_merge(
                    $dimensions,
                    ['errorcode' => $e->getCode()]
                )
            );
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

        try
        {
            $response = DomainUtils::sendExternalRequest(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method']);
        }
        catch (Throwable $e)
        {
            $this->trace->error(TraceCode::MERCHANT_SERVICEABILITY_EXTERNAL_CALL_ERROR,
                [
                    'request'   => $request,
                    'type'      => get_class($e),
                    'message'   => $e->getMessage(),
                    'code'      => $e->getCode(),
                    'trace'     => $e->getTraceAsString(),
                ]
            );

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
     * @param $settings
     * @param $amount
     * @return void
     * @throws Exception\BadRequestException
     */
    public function getFees($settings, $amount): int
    {
        $feeSetting = $settings ?? null;
        $feeRule = null;
        $fee = 0;
        if ($feeSetting !== null)
        {
            $feeRule = new FeeRule(json_decode($feeSetting, true));
            $feeRule->validate();
        }
        if ($feeRule === null)
        {
            return $fee;
        }
        if ($feeRule->data[Constants::FEE_RULE_TYPE] === 'slabs')
        {
           $fee = $this->getFeeFromSlabs($amount, $feeRule->data['slabs']);
        }
        else
        {
            $fee = $feeRule->getFee();
        }
        return $fee;
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

    protected function fetchCityAndState(array $address)
    {
        return $this->repo->zipcode_directory->findByZipcodeAndCountry($address['zipcode'], $address['country']);
    }

    protected function isZipcodeResponseValid(array $response): bool
    {
        return ($response['city'] !== '' && $response['state'] !== '' && $response['state_code'] !== '');
    }


    /**
     * @param $shippingMethodProviderConfig
     * @param $orderId
     * @param Base\PublicEntity $order
     * @param $orderMeta
     * @param $address
     * @param array $decodedResponse
     * @param Exception\BadRequestException $ex
     * @param $merchantOrderId
     * @param $mockResponse
     * @param array $dimensions
     * @return array
     * @throws Exception\BadRequestException
     */
    protected function shippingProviderOldFlow(
        $shippingMethodProviderConfig,
        $orderId,
        Base\PublicEntity $order,
        $orderMeta,
        $address,
        array $decodedResponse,
        $merchantOrderId,
        $mockResponse,
        array $dimensions,
        ?string $shippingVariant
    ): array
    {
        if ($shippingMethodProviderConfig !== null) {
            return (new Providers())->shippingResponseFromShippingProviderConfig(
                $orderId,
                $order,
                $orderMeta,
                $address,
                $shippingMethodProviderConfig,
                $shippingVariant
            );
        }

        $serviceabilityUrlConfig = $this->merchant->getShippingInfoUrlConfig();

        if ($serviceabilityUrlConfig === null) {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_SERVICEABILITY_URL_NOT_CONFIGURED);
        }

        $serviceabilityUrl = $serviceabilityUrlConfig->getValue();

        try {
            // Sending array for backward compatibility (bulk api)
            $response = $this->sendMerchantShippingInfoRequest(
                $orderId,
                $merchantOrderId,
                [array_merge($address, [self::SHIPPING_INFO_ID => 0])],
                $serviceabilityUrl,
                $mockResponse);

            $decodedResponse = json_decode($response->body, true);
            $decodedResponse = $decodedResponse[self::SHIPPING_INFO_ADDRESSES][0];
            if (isset($decodedResponse[self::SHIPPING_INFO_ID])) {
                unset($decodedResponse[self::SHIPPING_INFO_ID]);
            }
        } catch (Throwable $exception) {
            $this->trace->error(TraceCode::ERROR_EXCEPTION, ['error' => $exception->getMessage()]);

            throw new Exception\BadRequestException(
                ErrorCode::SERVER_ERROR_MERCHANT_SERVICEABILITY_EXTERNAL_CALL_EXCEPTION,
                null,
                null,
                'Unable to check pincode serviceability right now. Try again in some time');
        }
        if (json_last_error() !== JSON_ERROR_NONE || !isset($response) || $response->status_code !== 200) {
            $this->trace->count(Metric::MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_FAILURE_COUNT,
                array_merge(
                    $dimensions,
                    ['errorcode' => ErrorCode::SERVER_ERROR_MERCHANT_SERVICEABILITY_EXTERNAL_CALL_EXCEPTION]
                )
            );
            throw new Exception\BadRequestException(
                ErrorCode::SERVER_ERROR_MERCHANT_SERVICEABILITY_EXTERNAL_CALL_EXCEPTION, null, null, 'Unable to check pincode serviceability right now. Try again in some time');
        }

        try {
            (new Validator())->setStrictFalse()->validateInput("addressShippingInfoResponse", $decodedResponse);
        } catch (Throwable $e) {
            $this->trace->count(Metric::MERCHANT_EXTERNAL_SHIPPING_INFO_CALL_FAILURE_COUNT,
                ['errorcode' => ErrorCode::SERVER_ERROR_MERCHANT_SERVICEABILITY_EXTERNAL_CALL_EXCEPTION]);

            throw new Exception\BadRequestException(
                ErrorCode::SERVER_ERROR_MERCHANT_SERVICEABILITY_EXTERNAL_CALL_EXCEPTION, null, null, 'Unable to check pincode serviceability right now. Try again in some time');
        }

        return $decodedResponse;
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
    protected function cacheMerchantShippingInfo($orderId, $address, $orderAmount): void
    {
        $this->app['cache']->put(
            $this->getShippingInfoCacheKey($orderId, $address, $orderAmount),
            $address,
            self::SHIPPING_INFO_CACHE_VALIDITY);

        //backward compatibility
        $this->app['cache']->put(
            $this->getShippingInfoOldCacheKey($orderId, $address),
            $address,
            self::SHIPPING_INFO_CACHE_VALIDITY);
    }

    /**
     * @param $orderId
     * @param $address
     * @return string
     */
    private function getShippingInfoCacheKey($orderId, $address, $orderAmount = 0): string
    {
        $zipcode = $address['zipcode'] ?? "";
        $state = $address['state'] ?? "";
        $amount = (string) $orderAmount;

        return self::SHIPPING_INFO_CACHE_KEY_PREFIX
            . $this->merchant->getId()
            . "_"
            . $orderId
            . "_"
            . $amount
            . "_"
            . $zipcode
            . "_"
            . $state
            . "_"
            . $address['country'];
    }

    /**
     * @param $orderId
     * @param $address
     * @return string
     */
    private function getShippingInfoOldCacheKey($orderId, $address): string
    {
        $zipcode = $address['zipcode'] ?? "";
        $state = $address['state'] ?? "";

        return self::SHIPPING_INFO_CACHE_KEY_PREFIX
            . $this->merchant->getId()
            . "_"
            . $orderId
            . "_"
            . $zipcode
            . "_"
            . $state
            . "_"
            . $address['country'];
    }

    /**
     * @param $address
     * @return mixed
     */
    protected function getPincodeAndState($address)
    {
        try {
            try {

                $response = $this->app['pincodesearch']->fetchCityAndStateFromPincode($address['zipcode'], true, true, $address['country']);

            } catch (Throwable $e) {

                if ($e->getCode() === ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND)
                {
                    //fetch details from google api
                    $response = $this->app['pincodesearch']->fetchCityAndStateFromPincodeAndCountry($address['zipcode'], $address['country']);
                }
                else
                {
                    throw $e;
                }
            }
        } catch (Throwable $ex)
        {
            $response = ['city' => '', 'state' => '', 'state_code' => ''];
        }
        if ($this->isZipcodeResponseValid($response) === false)
        {
            $dbResponse = $this->fetchCityAndState($address);
            if (empty($dbResponse) === false)
            {
                $response = [
                    'city' => $dbResponse['city'],
                    'state' => $dbResponse['state'],
                    'state_code' => $dbResponse['state_code'],
                ];
            }
            else
            {
                $this->trace->count(Metric::ZIP_CODE_WITHOUT_ADDRESS_FOUND_COUNT);
                $this->trace->error(TraceCode::ZIP_CODE_WITHOUT_ADDRESS_FOUND_REQUEST, [
                    'zipcode' => $address['zipcode'],
                    'country' => $address['country'],
                ]);
            }
        }
        $address['city'] = $response['city'];

        $address['state'] = $response['state'];

        $address['state_code'] = $response['state_code'];

        $this->trace->debug(TraceCode::FETCH_CITY_STATE_RESULT, ['pincode' => $address['zipcode'],
            'country' => $address['country'],
            'city' => $response['city'],
            'state' => $response['state'],
            'state_code' => $response['state_code']]);

        return $address;
    }

    protected function traceResponseTime(string $metric, int $startTime, $dimensions = [])
    {
        $duration = millitime() - $startTime;

        $this->trace->histogram($metric, $duration, $dimensions);
    }


    /**
     * If country does not have zipcodes, will return the $address, with city=NA , if empty
     * If country is india, we'll use location API
     * If non of the above, we'll use location API
     */
    protected function getCountryAndStateBasedOnZipcode($address)
    {
        if (in_array(strtoupper($address['country']),ShippingInfoConstants::countryWithNoZipcodes) === true)
        {
            return $address;
        }

        return $this->getPincodeAndState($address);
    }

    /**
     * @param array $input
     *
     * @return array|null
     * @throws \RZP\Exception\BadRequestException
     * @throws \RZP\Exception\BadRequestValidationFailureException
     * @throws \Throwable
     */
    private function getNocodeAppsResponseIfApplicable(array $input): null | array
    {
        if (empty($this->merchant) === true)
        {
            return null;
        }

        if(!isset($input[self::SHIPPING_INFO_ADDRESSES]) || !isset($input['order_id']))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_SERVICEABILITY_INVALID_INPUT
            );
        }

        $orderId = $input['order_id'];
        /**
         * @var $order Entity
         */
        $order = $this->repo->order->findByPublicIdAndMerchant($orderId, $this->merchant);

        return $this->getNocodeAppsResponseFor1ccOrder($order, $input);
    }

    /**
     * @param \RZP\Models\Order\Entity $order
     * @param array                    $input
     *
     * @return array[]|null
     * @throws \RZP\Exception\BadRequestException
     */
    private function getNocodeAppsResponseFor1ccOrder(Entity $order, array $input)
    {
        $orderMeta = array_first($order->orderMetas ?? [], function ($orderMeta)
        {
            return $orderMeta->getType() === \RZP\Models\Order\OrderMeta\Type::ONE_CLICK_CHECKOUT;
        });

        if($orderMeta === null)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_1CC_ORDER);
        }

        $productType = $order->getProductType();

        if (ProductType::IsForNocodeApps($productType) === false)
        {
            return null;
        }

        $addresses = $input[self::SHIPPING_INFO_ADDRESSES];

        if (count($addresses) !== 1)
        {
            throw  new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_SERVICEABILITY_INVALID_INPUT
            );
        }

        $address = $addresses[0];

        (new Validator())->setStrictFalse()->validateInput("shippingInfoRequest", $address);

        (new Validator())->validateStateCode($address);

        $address = $this->getCountryAndStateBasedOnZipcode($address);

        $address[Fields::SHIPPING_FEE] = 0;
        $address[self::SERVICEABLE] = true;
        $address[self::COD] = false;
        $address[Fields::COD_FEE] = 0;

        return [self::SHIPPING_INFO_ADDRESSES => [$address]];
    }

    // For merchants using single shipping method we take the top level values.
    // In case multiple shipping methods is enabled, we iterate over the loop and record every value.
    protected function recordShippingInfoResp(array $address, array $dimensions): void {
      if (empty($address['shipping_methods']) === true) {
        $this->trace->count(Metric::MERCHANT_SHIPPING_INFO_RESPONSE_COUNT, [
            'serviceable' => $address['serviceable'],
            'cod'         => $address['cod'],
            'platform'    => $dimensions['platform'],
        ]);
        return;
      }
      $methods = $address['shipping_methods'];
      for ($i=0; $i < count($methods); $i++) {
        $this->trace->count(Metric::MERCHANT_SHIPPING_INFO_RESPONSE_COUNT, [
            'serviceable' => $methods[$i]['serviceable'],
            'cod'         => $methods[$i]['cod'],
            'platform'    => $dimensions['platform'],
        ]);
      }
    }
}
