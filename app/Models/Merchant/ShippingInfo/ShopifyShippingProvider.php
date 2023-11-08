<?php

namespace RZP\Models\Merchant\ShippingInfo;

use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Models\Merchant\OneClickCheckout\Utils\CommonUtils;
use RZP\Models\Merchant\OneClickCheckout\Shopify\StateMap;
use RZP\Models\Order\Entity;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\OneClickCheckout\ShippingMethodProvider\Type;
use RZP\Models\Merchant\OneClickCheckout\MigrationUtils\SplitzExperimentEvaluator;
use RZP\Models\Merchant\OneClickCheckout\ShippingMethodProvider\FeeRule;
use RZP\Models\Order\OrderMeta\Order1cc\Fields;
use RZP\Models\Order\ProductType;
use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Models\Merchant\Slab;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\Validator;
use RZP\Models\Merchant\OneClickCheckout\Shopify;
use RZP\Models\Merchant\Merchant1ccConfig;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\OneClickCheckout\DomainUtils;
use RZP\Models\Merchant\OneClickCheckout\ShippingMethodProvider\Constants;
use RZP\Models\Merchant\ShippingInfo\Constants as ShippingInfoConstants;
use RZP\Models\Customer;

class ShopifyShippingProvider extends Base\Service
{

    const SECOND = 1;
    const MINUTE = 60 * self::SECOND;
    const HOUR   = 60 * self::MINUTE;

    const SHIPPING_INFO_ID               = 'id';
    const SHIPPING_INFO_ADDRESSES        = 'addresses';
    const SHIPPING_INFO_ADDRESS          = 'address';
    const SHIPPING_INFO_CACHE_KEY_PREFIX = 'SHIPPING_INFO_';
    const SHIPPING_INFO_CACHE_VALIDITY   = 30 * self::MINUTE; // 30 minutes
    const SERVICEABLE                    = 'serviceable';
    const COD                            = 'cod';
    const DISABLE_SHIPPING_CACHE_RESET   = 'disable_shipping_cache_reset'; //shipping cache fix backward compatibility
    const TAX_DETAILS                    = 'tax_details';
    const DEFAULT_SHIPPING_VARIANT       = "__default";
    const TAX_DETAILS_CACHE_KEY_PREFIX   = 'SHIPPING_INFO_TAX_DETAILS_';

    const FETCH_CACHED_CART_URL = 'v1/magic/cache/cart/fetch';

    const SHIPPING_SERVICE_METHODS_EVALUATE_PATH = 'twirp/rzp.shipping.shipping_info_api.v1.ShippingInfoAPI/Evaluate';

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
        if ($this->merchant === null or $this->merchant->isFeatureEnabled(FeatureConstants::ONE_CLICK_CHECKOUT) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $decodedResponse = [];

        $ex = '';

        $dimensions = [
            'mode' => $this->mode,
        ];

        $this->trace->count(Metric::MERCHANT_SHIPPING_INFO_CHECK_CALL_COUNT, $dimensions);

        try
        {
            $serviceabilityCheckStartTime = millitime();

            $this->validateShippingInfoRequestObj($input);

            $address = $this->getCountryAndStateBasedOnZipcode($input[self::SHIPPING_INFO_ADDRESSES][0]);

            $mockResponse = $input['mock_response'] ?? null;

            unset($input['mock_response']);

            $platformConfig = $this->merchant->getMerchantPlatformConfig();

            if ($platformConfig !== null)
            {
                $dimensions = array_merge($dimensions, ['platform' => $platformConfig->getValue()]);
            }

            $taxDetails = null;

            $shippingMethodProviderConfig = $this->merchant->getShippingMethodProvider();

            $isDigitalProduct = false;

            $referenceId = $input['reference_id'];

            [$decodedResponse, $cart, $cachedResponse] = $this->fetchShippingResponseFromUsingCheckoutId(
                $input,
                $address,
                $shippingMethodProviderConfig
            );

            if ($cachedResponse !== null)
            {
                return $cachedResponse;
            }

            $orderAmount = (int)(floatval($cart['total_price']));

            if (isset($decodedResponse['is_digital_product']) === true)
            {
                $isDigitalProduct = $decodedResponse['is_digital_product'];
                unset($decodedResponse['is_digital_product']);
            }

            $isTaxExpEnabled = (new CommonUtils())->isTaxesExpEnabled();

            if (empty($decodedResponse['tax_details']) === false && $isTaxExpEnabled === true)
            {
                $taxDetails = $decodedResponse['tax_details'];
                unset($decodedResponse['tax_details']);
            }

            // get customer email and contact for cod engine
            $customer = $this->getMagicCustomerDetails();
            $customerContact = $customer->getContact();
            $customerEmail = $customer->getEmail();

            // Backwards compatibility for merchant serviceability url/shopify that does not return methods
            $decodedResponse = $this->convertOldFormatToShippingMethods($decodedResponse);

            $address = array_merge($decodedResponse, $address);
            foreach ($address['shipping_methods'] as &$method)
            {
                if (isset($method['cod_fee']) === false)
                {
                    $method['cod_fee'] = $this->getFeeFromSlab(
                        $orderAmount,
                        Slab\Type::COD_SLAB);
                }

                if (isset($method['shipping_fee']) === false)
                {
                    $method['shipping_fee'] = $this->getFeeFromSlab(
                        $orderAmount,
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
                $address['cod'] = $this->getCodServiceabilityFromSlabs($orderAmount);
            }

            $configs = $this->repo->merchant_1cc_configs->findByMerchantAndConfigArray(
                $this->merchant->getId(),
                [Merchant1ccConfig\Type::COD_ENGINE, Merchant1ccConfig\Type::COD_ENGINE_TYPE]
            );

            $codEngineConfigs = [];
            foreach ($configs as $config)
            {
                $codEngineConfigs[$config->getConfig()] = $config->getValue();
            }
            // It will be executed if merchant has opted for magic-cod-engine
            if ($codEngineConfigs[Merchant1ccConfig\Type::COD_ENGINE] === '1')
            {
                $rzpOrderId = UniqueIdEntity::generateUniqueId(); // getting random 14 digit unique id to maintain the contract
                $orderAmountInRupee = $orderAmount / pow(10, 2);
                $roundOrderAmount = round($orderAmountInRupee) * 100;
                $products = [];
                $items = $cart['items'];
                foreach ($items as $lineItems)
                {
                    $product = [];
                    $product['id'] = strval($lineItems['product_id']);
                    array_push($products, $product);
                }

                $customerInfo = [];
                $customerInfo['email'] = $customerEmail;
                $customerInfo['phone'] = $customerContact;
                $customerInfo['ip'] = $this->app['request']->ip();

                $inputOrder = [
                    'id'                  => '',  // sending empty order Id as order is not present
                    'amount'              => $roundOrderAmount,
                    'products'            => $products,
                    'shopify_checkout_id' => $referenceId,
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
                    'country_code' => strtoupper($address['country']),
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
                    catch (\Exception $ex)
                    {
                        $this->trace->count(
                            Metric::MAGIC_COD_ENGINE_EVALUATE_API_ERROR_COUNT,
                            array_merge($dimensions, ['code' => $ex->getCode()])
                        );

                        $this->trace->error(TraceCode::MAGIC_COD_ENGINE_EVALUATE_CALL_ERROR,
                            [
                                'code'        => $ex->getCode(),
                                'message'     => $ex->getMessage(),
                                'merchant_id' => $this->merchant->getMerchantId(),
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
                    if ($this->merchant->isFeatureEnabled(FeatureConstants::ONE_CC_SHOPIFY_MULTIPLE_SHIPPING))
                    {
                        foreach ($address['shipping_methods'] as &$method)
                        {
                            $method['cod'] = $isCodEligible;
                            $method['cod_fee'] = $codFee;
                        }
                    }
                }
            }


            $this->cacheMerchantShippingInfo($referenceId, $address, $orderAmount);
            $this->cacheTaxDetailsForShippingAddress($referenceId, $address, $taxDetails, $orderAmount);
            $this->recordShippingInfoResp($address, $dimensions);

            return [
                self::SHIPPING_INFO_ADDRESSES => [$address],
                self::TAX_DETAILS             => $taxDetails,
            ];

        }
        catch (\Throwable $e)
        {
            $ex = $e;
            throw $e;
        }
        finally
        {
            if (empty($ex) === true)
            {
                $this->trace->info(TraceCode::MERCHANT_ADDRESS_SHIPPING_INFO_REQUEST,
                    array_merge($dimensions,
                        [
                            'response'  => $decodedResponse,
                            'exception' => $ex,
                        ])
                );
            }
            else
            {
                $internalErrorCode = "";
                if (($ex instanceof Exception\BaseException) === true)
                {
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
                            'response'            => $decodedResponse,
                            'internal_error_code' => $internalErrorCode,
                            'exception'           => $ex->getMessage(),
                        ])
                );
            }
        }
    }

    public function getShippingVariant($lineItems): ?string
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
                foreach ($lineItems as $item)
                {
                    $productType = $item['product_type'] ?? '';

                    if ($shippingVariantsDict[$productType] === 1)
                    {
                        $productTypes[$productType] = 1;
                    }
                    else
                    {
                        $productTypes[self::DEFAULT_SHIPPING_VARIANT] = 1;
                    }
                }
                foreach ($shippingVariants as $shippingVariant)
                {
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
                    'id'          => 'default',
                    'name'        => 'default',
                    'description' => 'default',
                ],
            ],
        ];
        foreach ([
                     'shipping_fee',
                     'serviceable',
                     'cod',
                     'cod_fee',
                 ] as $key)
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

    public function getTaxDetailsFromCache($orderId, $address, $orderAmount)
    {
        //gets the taxes for the shipping address
        $taxesCachedResponse = $this->app['cache']->get(
            $this->getShippingInfoTaxCacheKey($orderId, $address, $orderAmount));

        if (!empty($taxesCachedResponse))
        {
            return $taxesCachedResponse;
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
     * @param $orderAmount
     * @param $taxDetails
     */
    protected function cacheTaxDetailsForShippingAddress($orderId, $address, $taxDetails, $orderAmount): void
    {
        $this->app['cache']->put(
            $this->getShippingInfoTaxCacheKey($orderId, $address, $orderAmount),
            $taxDetails,
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
        $amount = (string)$orderAmount;

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
        try
        {
            try
            {

                $response = $this->app['pincodesearch']->fetchCityAndStateFromPincode($address['zipcode'], true, true, $address['country']);

            }
            catch (Throwable $e)
            {

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
        }
        catch (Throwable $ex)
        {
            $response = ['city' => '', 'state' => '', 'state_code' => ''];
        }
        if ($this->isZipcodeResponseValid($response) === false)
        {
            $dbResponse = $this->fetchCityAndState($address);
            if (empty($dbResponse) === false)
            {
                $response = [
                    'city'       => $dbResponse['city'],
                    'state'      => $dbResponse['state'],
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

        $this->trace->debug(TraceCode::FETCH_CITY_STATE_RESULT, [
            'pincode'    => $address['zipcode'],
            'country'    => $address['country'],
            'city'       => $response['city'],
            'state'      => $response['state'],
            'state_code' => $response['state_code'],
        ]);

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
        if (in_array(strtoupper($address['country']), ShippingInfoConstants::countryWithNoZipcodes) === true)
        {
            return $address;
        }

        return $this->getPincodeAndState($address);
    }

    // For merchants using single shipping method we take the top level values.
    // In case multiple shipping methods is enabled, we iterate over the loop and record every value.
    protected function recordShippingInfoResp(array $address, array $dimensions): void
    {
        if (empty($address['shipping_methods']) === true)
        {
            $this->trace->count(Metric::MERCHANT_SHIPPING_INFO_RESPONSE_COUNT, [
                'serviceable' => $address['serviceable'],
                'cod'         => $address['cod'],
                'platform'    => $dimensions['platform'],
            ]);
            return;
        }
        $methods = $address['shipping_methods'];
        for ($i = 0; $i < count($methods); $i++)
        {
            $this->trace->count(Metric::MERCHANT_SHIPPING_INFO_RESPONSE_COUNT, [
                'serviceable' => $methods[$i]['serviceable'],
                'cod'         => $methods[$i]['cod'],
                'platform'    => $dimensions['platform'],
            ]);
        }
    }

    /**
     * @param $orderId
     * @param $address
     * @param int $orderAmount
     * @return string
     */
    protected function getShippingInfoTaxCacheKey($orderId, $address, $orderAmount = 0): string
    {
        $zipcode = $address['zipcode'] ?? "";
        $state = $address['state'] ?? "";
        $amount = (string)$orderAmount;

        return self::TAX_DETAILS_CACHE_KEY_PREFIX
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
     * @param array $input
     * @throws Exception\BadRequestException
     */
    protected function validateShippingInfoRequestObj(array $input): void
    {
        if (!isset($input[self::SHIPPING_INFO_ADDRESSES]))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_SERVICEABILITY_INVALID_INPUT
            );
        }

        if (!isset($input['reference_id']) || !isset($input['reference_type']))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_SERVICEABILITY_INVALID_INPUT
            );
        }


        $addresses = $input[self::SHIPPING_INFO_ADDRESSES];
        if (count($addresses) !== 1)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_SERVICEABILITY_INVALID_INPUT
            );

        }

        $address = $addresses[0];

        (new Validator())->setStrictFalse()->validateInput("shippingInfoRequest", $address);

        (new Validator())->validateStateCode($address);
    }

    /**
     * @throws ServerErrorException|BadRequestException
     * @throws \Throwable
     */
    public function fetchShippingResponseFromUsingCheckoutId(array $input, array $address,
                                                                   $shippingMethodProviderConfig): array
    {
        try
        {
            $referenceId = $input['reference_id'];

            $cartResp = $this->getCartDetailsFromConsumerApp($referenceId);

            (new Validator())->setStrictFalse()->validateInput("shippingInfoFetchCartResponse", $cartResp);

            $cart = $cartResp['cart'];

            $shopifyShippingOverride = (new Merchant1ccConfig\Core())->isShopifyShippingOverrideSet($this->merchant->getId());

            // ShippingVariant is required for merchants with multiple shipping configurations with the same mid
            $shippingVariant = $this->getShippingVariant($cart['items']);

            $orderAmount = (int)(floatval($cart['total_price']));

            $cachedShippingInfo = $this->getCachedShippingInfo($referenceId, $address, $orderAmount);

            if ($cachedShippingInfo !== null)
            {
                return [null, $cart, $cachedShippingInfo];
            }

            $notes = [
                'storefront_id' => $input['reference_id'],
            ];

            $decodedResponse = $this->getShopifyShippingResponse(
                $input['reference_id'],
                $address,
                $notes,
                $orderAmount,
                $shippingMethodProviderConfig,
                $shopifyShippingOverride,
                $shippingVariant
            );

            return [$decodedResponse, $cart, $cachedShippingInfo];

        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::SHIPPING_INFO_USING_CHECKOUT_ID_ERROR, [
                'error_message' => $e->getMessage(),
                'exception'     => $e->getTrace(),
            ]);
        }
        throw  $e;
    }

    public function getCachedShippingInfo($referenceId, $address, $orderAmount): ?array
    {
        $cachedResponse = $this->getShippingInfoFromCache($referenceId, $address, $orderAmount);

        if (!empty($cachedResponse))
        {

            $cacheTaxDetails = $this->getTaxDetailsFromCache($referenceId, $address, $orderAmount);

            return [
                'addresses'   => [$cachedResponse],
                'tax_details' => $cacheTaxDetails,
            ];
        }

        return null; // Return null if no cached response is unavailable
    }

    /**
     * This will fetch cart details from consumer-app , which is stored in cache
     * while creating checkout for shopify flow
     * @param string $referenceId
     * @return array
     * @throws BadRequestException
     * @throws ServerErrorException
     */
    public function getCartDetailsFromConsumerApp(string $referenceId): array
    {
        try
        {

            $merchantId = $this->merchant->getId();

            $query = "?merchant_id={$merchantId}&shopify_checkout_id={$referenceId}";

            $url = self::FETCH_CACHED_CART_URL . $query;

            return $this->app['integration_service_client']->sendRequest($url, Requests::GET);

        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::SHIPPING_INFO_CART_DETAILS_NOT_FOUND, [
                'error_message' => $e->getMessage(),
                'exception'     => $e->getTrace(),
            ]);
            throw new ServerErrorException('Could not fetch cart from consumer app',
                ErrorCode::SERVER_ERROR);
        }
    }

    protected function getShopifyShippingResponse(
        $referenceId,
        $address,
        $notes,
        $orderAmount,
        $shippingMethodProviderConfig,
        $shopifyShippingOverride,
        $shippingVariant
    ): array
    {
        if ($shopifyShippingOverride === false) {
            $decodedResponse = (new Shopify\Service)->getShippingInfo([
                'order_id' => $referenceId,
                'address' => array_merge($address, ['id' => 0]),
            ]);

            if (empty($decodedResponse['use_fallback']) === false) {
                unset($decodedResponse['use_fallback']);
                if ($shippingMethodProviderConfig !== null) {
                    $decodedResponse = $this->shippingResponseFromShippingProviderConfig(
                        $referenceId,
                        $notes,
                        $orderAmount,
                        $address,
                        $shippingMethodProviderConfig,
                        $shippingVariant
                    );
                }
            }
        } else {
            $decodedResponse = $this->shippingResponseFromShippingProviderConfig(
                $referenceId,
                $notes,
                $orderAmount,
                $address,
                $shippingMethodProviderConfig,
                $shippingVariant
            );
        }

        return $decodedResponse;
    }

    /**
     * @throws BadRequestException
     */
    public function getMagicCustomerDetails()
    {
        try
        {
            $customer = (new Customer\Service)->getMagicCustomer();
            if ($customer === null)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED);
            }

            return $customer;
        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::SHIPPING_INFO_USER_NOT_FOUND, [
                'error_message' => $e->getMessage(),
                'exception'     => $e->getTrace(),
            ]);
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED);
        }
    }

    public function shippingResponseFromShippingProviderConfig(
        $referenceId,
        $notes,
        $orderAmount,
        $address,
        $shippingMethodProviderConfig,
        ?string $shippingVariant
    ): array
    {
        $shippingMethodProviderConfigJson = $shippingMethodProviderConfig->getValueJson();
        $shippingProviderType = $shippingMethodProviderConfigJson[Constants::PROVIDER_TYPE] ?? Type::SHIPROCKET;
        $this->trace->count(Metric::SHIPPING_SERVICE_CALL_COUNT, ['mode' => $this->mode ]);
        switch ($shippingProviderType)
        {
            case Type::DEMO:
            case Type::RAZORPAY:
                $decodedResponse = $this->getShippingMethods(
                    $shippingMethodProviderConfigJson,
                    $address,
                    $referenceId,
                    $orderAmount,
                    $notes,
                    $shippingVariant
                );
                break;
            default:
                $decodedResponse = $this->getShippingInfoForShippingMethodProvider($shippingMethodProviderConfig,
                    $address,
                    '');
                break;
        }
        return $decodedResponse;
    }


    protected function getShippingMethods(
        $shippingMethodProviderConfig,
        $address,
        $referenceId,
        $lineItemsTotal,
        $notes,
        ?string $shippingVariant
    ): array
    {
        $address['country_code'] = $address['country'];
        unset($address['country']);

        $address['zip_code'] = $address['zipcode'];
        unset($address['zipcode']);

        $orderId = ''; // order_id not present
        $shippingInfo = $this->evaluate(
            $shippingMethodProviderConfig['shipping_provider_id'],
            $address,
            $lineItemsTotal,
            $orderId,
            $this->merchant->getId(),
            $notes,
            $shippingVariant,
            $referenceId
        );
        return $this->parseShippingServiceResponse($address, $shippingInfo);
    }

    /**
     * This method calls new API of 1cc-shipping-service
     * It will get shipping info for all the merchants except shopify merchants.
     */

    /**
     * @param $address
     * @param array $shippingInfo
     * @return mixed
     */
    protected function parseShippingServiceResponse($address, array $shippingInfo)
    {
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
            'id',
        ];

        if (empty($shippingInfo['shipping_methods']) === false)
        {
            // Filling empty values as protobuf omits empty fields and converting strings to ints due to protobuf serialization
            for ($i = 0; $i < count($shippingInfo['shipping_methods']); $i++)
            {
                foreach ($keys as $key)
                {
                    if (isset($shippingInfo['shipping_methods'][$i][$key]) === false)
                    {
                        switch ($key)
                        {
                            case 'id':
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
        }
        return array_merge($address, $shippingInfo);
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

    public function evaluate(
        string $shippingProviderId,
        array $location,
        int $lineItemsTotal,
        string $orderId,
        string $merchantId,
        $notes,
        ?string $shippingVariant,
        ?string $shopifyCheckoutId
    )
    {
        $request = [
            'delivery_location' => $location,
            'order'             => [
                'id' => "",
                'line_items_total' => $lineItemsTotal,
                'notes' => $notes,
                'shopify_checkout_id' => $shopifyCheckoutId,
            ],
            'merchant_id'       => $merchantId,
            'shipping_provider_id' => $shippingProviderId,
            'shipping_variant'  => $shippingVariant,
        ];
        return $this->app['shipping_service_client']->sendRequest(self::SHIPPING_SERVICE_METHODS_EVALUATE_PATH, $request, Requests::POST);
    }
}
