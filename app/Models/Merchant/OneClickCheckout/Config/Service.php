<?php

namespace RZP\Models\Merchant\OneClickCheckout\Config;

use GuzzleHttp\Client;
use RZP\Exception\ServerErrorException;
use RZP\Models\Merchant\Account;
use RZP\Models\Base\UniqueIdEntity;
use Google\Service\Compute\Condition;
use Monolog\Logger;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Http\Request\Requests;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Core;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Merchant1ccConfig\Type;
use RZP\Models\Merchant\OneClickCheckout\Constants;
use RZP\Models\Merchant\OneClickCheckout\Shopify\Utils as ShopifyUtils;
use RZP\Models\Merchant;
use RZP\Services\KafkaProducer;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Service as MerchantService;
use RZP\Models\Merchant\OneClickCheckout\ShippingMethods\Service as ShippingService;
use RZP\Models\Key;

class Service extends Base\Service
{

    const MUTEX_LOCK_TTL_SEC = 60;

    const MAX_RETRY_COUNT = 1;

    const MAX_RETRY_DELAY_MILLIS = 1 * 30 * 1000;

    protected $mutex;

    const MUTEX_KEY = 'merchant_1cc_configs';

    const WHITELIST_COUPONS_UPLOAD_PATH = 'v1/magic/coupons/csv?key_id=';
    const MERCHANT_METHODS_OFFER_MUTEX_KEY_PREFIX = 'mer_1cc_configs_methods_offers';
    const SECOND    = 1;
    const MINUTE    = 60 * self::SECOND;
    const CACHE_TTL = 30 * self::MINUTE;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * @throws \Throwable
     */
    public function update1ccConfig($input)
    {
        if (isset($input['platform']) == false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, 'Platform is required');
        }

        if ($input['platform'] === Constants::SHOPIFY)
        {
            (new Validator())->setStrictFalse()->validateInput(Constants::SHOPIFY, $input);
        }
        else
        {
            (new Validator())->setStrictFalse()->validateInput(Constants::NATIVE, $input);
        }

        global $shippingProvider;
        $this->repo->transaction(
            function () use ($input)
            {
                $reset = false;

                $updatePlatform = $input['platform'];

                $merchantPlatform = null;
                $merchantPlatformConfig = $this->merchant->getMerchantPlatformConfig();

                if ($merchantPlatformConfig !== null)
                {
                    $merchantPlatform = $merchantPlatformConfig->getValue();
                }

                if ($updatePlatform !== $merchantPlatform)
                {
                    if ($merchantPlatform !== null){
                        $this->repo->merchant_1cc_auth_configs->deleteByMerchantAndPlatform(
                            $this->merchant->getId(),
                            $merchantPlatform
                        );
                    }

                    $this->reset1ccConfig($merchantPlatform);

                    $reset = true;

                    if ($merchantPlatformConfig !== null)
                    {
                        $this->repo->merchant_1cc_configs->delete($merchantPlatformConfig);
                    }

                    (new Core)->associateMerchant1ccConfig(
                        Type::PLATFORM,
                        $updatePlatform
                    );
                }
                $GLOBALS['shippingProvider'] = [];
                if ($updatePlatform !== Constants::SHOPIFY)
                {
                    foreach ($input as $key => $value)
                    {
                        switch ($key)
                        {
                            case "shipping_info":
                                $GLOBALS['shippingProvider'] = array_merge($GLOBALS['shippingProvider'], ["url" => $value]);
                                (new Core)->associateMerchant1ccConfig(
                                    Type::SHIPPING_INFO_URL,
                                    $value
                                );
                                break;
                            case "list_promotions":
                                (new Core)->associateMerchant1ccConfig(
                                    Type::FETCH_COUPONS_URL,
                                    $value
                                );
                                break;
                            case "apply_promotion":
                                (new Core)->associateMerchant1ccConfig(
                                    Type::APPLY_COUPON_URL,
                                    $value
                                );
                                break;
                            case "shipping_slabs":
                                $GLOBALS['shippingProvider'] = array_merge($GLOBALS['shippingProvider'], ["shipping_slabs" => $value]);
                                (new \RZP\Models\Merchant\Service())->updateShippingSlabs(['slabs' => $value]);
                                break;
                            case "cod_slabs":
                                $GLOBALS['shippingProvider'] = array_merge($GLOBALS['shippingProvider'], ["cod_slabs" => $value]);
                                (new \RZP\Models\Merchant\Service())->updateCodSlabs(['slabs' => $value]);
                                break;
                        }
                    }
                }
                else
                {
                    $this->repo->merchant_1cc_auth_configs->deleteByConfig(
                        $this->merchant->getId(),
                        Constants::SHOPIFY,
                        Constants::SHOP_ID
                    );

                    $shopId = (new ShopifyUtils)->stripAndReturnShopId($input[Constants::SHOP_ID]);

                    $this->repo->merchant_1cc_auth_configs->create(
                        [
                            'merchant_id' => $this->merchant->getId(),
                            'platform'    => Constants::SHOPIFY,
                            'config'      => Constants::SHOP_ID,
                            'value'       => $shopId,
                        ]
                    );

                    $topic =  env('APP_MODE', 'prod').'-'. Constants::ONE_CC_MERCHANT_CONFIG;
                    try
                    {
                        $this->trace->info(TraceCode::STARTING_ONE_CC_MERCHANT_CONFIG_KAFKA_UPLOAD,
                            [
                                Constants::MERCHANT_ID => $this->merchant->getId(),
                                'topic' => $topic,
                                'broker' => env('QUEUE_KAFKA_CONSUMER_BROKERS')
                            ]);
                        $message = array(
                            Constants::MERCHANT_ID => $this->merchant->getId(),
                            Constants::PLATFORM => Constants::SHOPIFY,
                            'action' => 'one_cc_merchant_config_update',
                        );
                        (new KafkaProducer($topic, stringify($message)))->Produce();
                    }
                    catch (\Exception $e)
                    {
                        $this->trace->error(TraceCode::ONE_CC_MERCHANT_CONFIG_KAFKA_UPLOAD_FAILED,
                            [
                                'error' => $e->getMessage(),
                                Constants::MERCHANT_ID => $this->merchant->getId(),
                                'topic' => $topic
                            ]
                        );
                        $this->trace->count(TraceCode::ONE_CC_MERCHANT_CONFIG_KAFKA_UPLOAD_FAILED);
                    }
                }

                $updatedManualControlCodOrderFlag = isset($input[Type::MANUAL_CONTROL_COD_ORDER]) &&
                    $input[Type::MANUAL_CONTROL_COD_ORDER] === true;

                if ($updatePlatform === Constants::WOOCOMMERCE)
                {
                    if (isset($input[Type::API_KEY]) && isset($input[Type::API_SECRET]) && $updatedManualControlCodOrderFlag === true)
                    {
                        (new Merchant\OneClickCheckout\AuthConfig\Service())->updateWoocommerce1ccAuthConfig([
                            'merchant_id'           => $this->merchant->getId(),
                            Constants::API_KEY      => $input[Type::API_KEY],
                            Constants::API_SECRET   => $input[Type::API_SECRET]
                        ]);
                    }
                }

                if ($updatePlatform === Constants::NATIVE)
                {
                    if ((!(isset($input[Type::USERNAME]) && isset($input[Type::PASSWORD]) && isset($input[Type::ORDER_STATUS_UPDATE_URL]))) &&
                        ($updatedManualControlCodOrderFlag === true && isset($input[Type::MANUAL_CONTROL_COD_ORDER])))
                    {
                        $msg = 'username, password and order status url should be sent for native platform to enable manual control cod order';
                        throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR,'username, password and order status url are required', null, $msg);
                    }
                    if (isset($input[Type::USERNAME]) && isset($input[Type::PASSWORD]) &&
                        isset($input[Type::ORDER_STATUS_UPDATE_URL]) && $updatedManualControlCodOrderFlag === true)
                    {
                        (new Merchant\OneClickCheckout\AuthConfig\Service())->updateNative1ccAuthConfig([
                            'merchant_id'           => $this->merchant->getId(),
                            Constants::USERNAME     => $input[Type::USERNAME],
                            Constants::PASSWORD     => $input[Type::PASSWORD]
                        ]);
                        (new Core)->associateMerchant1ccConfig(
                            Type::ORDER_STATUS_UPDATE_URL,
                            $input[Type::ORDER_STATUS_UPDATE_URL]
                        );
                    }
                }

                /*
                 * shopify specific configs are also feature flags
                 * and cod_intelligence addition is handled above
                 */
                foreach ($input as $key => $value)
                {

                    if (in_array($key, Constants::COMMON_CONFIGS) === true && !in_array($key,Constants::INTELLIGENCE_CONFIGS)) {
                        $this->add1ccConfigFlags($input, $key);
                    }

                    if (in_array($key, Constants::SHOPIFY_SPECIFIC_CONFIGS) === true && $updatePlatform === Constants::SHOPIFY) {
                        $this->add1ccConfigFlags($input, $key);
                        if($key === Constants::ONE_CC_GA_ANALYTICS)
                        {
                            $this->app['magic_analytics_provider_service']->toggleBEGAAnalytics($this->merchant->getId(), $value);
                        }

                        /*
                         * Merchant can enable/disable fb_analytics from merchant dashboard
                         * Browser and Server side fb analytics will be enabled/disabled
                         * The following piece of code flips server side fb analytics events for a merchant
                         */
                        // disabling this for now
                        // Thread: https://razorpay.slack.com/archives/C03D4UC6UG0/p1676868399275769
                        //if($key === Constants::ONE_CC_FB_ANALYTICS)
                        //{
                        //    $this->app['magic_analytics_provider_service']->toggleBEFbAnalytics($this->merchant->getId(), $value);
                        //}
                    }

                    if (in_array($key, Constants::GIFT_CARD_CONFIGS) === true && $updatePlatform !== Constants::NATIVE) {
                        $this->add1ccConfigFlags($input, $key);
                    }
                }

                if (isset($input[Type::DOMAIN_URL])) {
                    (new Core)->associateMerchant1ccConfig(
                        Type::DOMAIN_URL,
                        $input[Type::DOMAIN_URL]
                    );
                }

                if (isset($input[Constants::COD_ENGINE_TYPE]) && $updatePlatform === Constants::SHOPIFY) {
                    (new Core)->associateMerchant1ccConfig(
                        Constants::COD_ENGINE_TYPE,
                        $input[Constants::COD_ENGINE_TYPE]
                    );
                }
            }
        );

        $this->update1ccIntelligenceConfig($input);

        $this->updatePrepayCodConfigs($input);

        $this->updateShippingInfoConfig($shippingProvider);

        if ( $input['platform'] === Constants::SHOPIFY && (isset($input[Type::ONE_CLICK_CHECKOUT]))) {

            $configOneClickCheckout = $this->merchant->get1ccConfig(Type::ONE_CLICK_CHECKOUT);
            $oneClickCheckoutValue = ($configOneClickCheckout !== null && $configOneClickCheckout->getValue() === "1") ? Constants::TRUE : Constants::FALSE;
            (new Merchant\OneClickCheckout\Shopify\Service())->controlMagicCheckout(Constants::ONE_CLICK_CHECKOUT_ENABLED, $oneClickCheckoutValue);
            $this->trace->info(
                TraceCode::MAGIC_CHECKOUT_ENABLED,
                [
                    'merchant_id' => $this->merchant->getId(),
                    'MAGIC_CHECKOUT_VALUE' => $oneClickCheckoutValue
                ]);

            if(isset($input[Type::ONE_CC_BUY_NOW_BUTTON]))
            {
                $configBuyNow = $this->merchant->get1ccConfig(Type::ONE_CC_BUY_NOW_BUTTON);
                $buyNowValue = ($configBuyNow !== null && $configBuyNow->getValue() === "1") ? Constants::TRUE : Constants::FALSE;
                $buyNowValue = $oneClickCheckoutValue === Constants::FALSE ? $oneClickCheckoutValue : $buyNowValue;
                (new Merchant\OneClickCheckout\Shopify\Service())->controlMagicCheckout(Constants::BUY_NOW_ENABLED, $buyNowValue);
                $this->trace->info(
                    TraceCode::BUY_NOW_BUTTON_ENABLED_OR_DISABLED,
                    [
                        'merchant_id' => $this->merchant->getId(),
                        'BUY_NOW_ENABLED/DISABLED' => $buyNowValue
                    ]);
            }
        }
    }

    protected function updatePrepayCodConfigs($input)
    {
        if (isset($input[Type::ONE_CC_PREPAY_COD_CONVERSION]) === true)
        {
            $prepayCod = $input[Type::ONE_CC_PREPAY_COD_CONVERSION];
            (new Validator())->validateInput('prepayCodConversion', $prepayCod);
            if ($prepayCod[Constants::ENABLED] === true)
            {
                switch ($prepayCod[Constants::CONFIGS][Constants::DISCOUNT][Constants::TYPE])
                {
                    case Constants::ZERO:
                        $prepayCod[Constants::CONFIGS][Constants::DISCOUNT][Constants::MINIMUM_ORDER_VALUE] = 0;
                        $prepayCod[Constants::CONFIGS][Constants::DISCOUNT][Constants::DISCOUNT_PERCENTAGE] = 0;
                        $prepayCod[Constants::CONFIGS][Constants::DISCOUNT][Constants::MAX_DISCOUNT] = 0;
                        break;
                    case Constants::FLAT:
                        $prepayCod[Constants::CONFIGS][Constants::DISCOUNT][Constants::DISCOUNT_PERCENTAGE] = 0;
                        break;
                }
            }

            $this->mutex->acquireAndRelease(
                self::MUTEX_KEY . ':' . $this->merchant->getId() . ':' . Type::ONE_CC_PREPAY_COD_CONVERSION,
                function () use ($prepayCod)
                {
                    (new Core())->associateMerchant1ccCODConfig(
                        Type::ONE_CC_PREPAY_COD_CONVERSION,
                        $prepayCod[Constants::ENABLED],
                        $prepayCod[Constants::CONFIGS] ?? []);
                },
                self::MUTEX_LOCK_TTL_SEC,
                ErrorCode::BAD_REQUEST_ANOTHER_1CC_CONFIG_OPERATION_IN_PROGRESS,
                self::MAX_RETRY_COUNT,
                self::MAX_RETRY_DELAY_MILLIS - 500,
                self::MAX_RETRY_DELAY_MILLIS,
                true
            );
        }
    }

    protected function update1ccIntelligenceConfig($input)
    {
        $updatedCodIntelligenceEnabledFlag = isset($input[Type::COD_INTELLIGENCE]) &&
            $input[Type::COD_INTELLIGENCE] === true;

        $updatedManualControlCodOrderFlag = isset($input[Type::MANUAL_CONTROL_COD_ORDER]) &&
            $input[Type::MANUAL_CONTROL_COD_ORDER] === true;

        $currentCodIntelligenceEnabledFlag = $this->merchant->getCODIntelligenceConfig();

        $currentManualControlCodOrderFlag = $this->merchant->getManualControlCodOrderConfig();

        if((isset($input[Type::COD_INTELLIGENCE]) && ($currentCodIntelligenceEnabledFlag !== $updatedCodIntelligenceEnabledFlag))||
            (isset($input[Type::MANUAL_CONTROL_COD_ORDER]) && ($currentManualControlCodOrderFlag !== $updatedManualControlCodOrderFlag)) )
        {
            if (($updatedCodIntelligenceEnabledFlag xor $updatedManualControlCodOrderFlag) &&
                ($currentCodIntelligenceEnabledFlag === false && $currentManualControlCodOrderFlag === false))
            {
                $topic =  env('APP_MODE', 'prod').'-'. Constants::RTO_MLMODEL_ASSIGNMENT;
                try
                {
                    $this->trace->info(TraceCode::STARTING_RTO_MLMODEL_ASSIGNMENT_KAFKA_UPLOAD,
                        [
                            'merchant_id' => $this->merchant->getId(),
                            'topic' => $topic
                        ]);
                    $message = array("merchant_id" => $this->merchant->getId());
                    (new KafkaProducer($topic, stringify($message)))->Produce();
                }
                catch (\Exception $e)
                {
                    $this->trace->error(TraceCode::RTO_MLMODEL_ASSIGNMENT_KAFKA_UPLOAD_FAILED,
                        [
                            'error' => $e->getMessage(),
                            'merchant_id' => $this->merchant->getId(),
                            'topic' => $topic
                        ]
                    );
                }
            }

            $this->mutex->acquireAndRelease(
                self::MUTEX_KEY . ':' . $this->merchant->getId() . ':' . Type::COD_INTELLIGENCE . ':' .
                Type::MANUAL_CONTROL_COD_ORDER,
                function () use ($updatedCodIntelligenceEnabledFlag, $updatedManualControlCodOrderFlag)
                {
                    (new Core())->associateMerchant1ccIntelligenceConfig(
                        Type::COD_INTELLIGENCE,
                        $updatedCodIntelligenceEnabledFlag
                    );
                    (new Core())->associateMerchant1ccIntelligenceConfig(
                        Type::MANUAL_CONTROL_COD_ORDER,
                        $updatedManualControlCodOrderFlag
                    );
                },
                self::MUTEX_LOCK_TTL_SEC,
                ErrorCode::BAD_REQUEST_ANOTHER_1CC_CONFIG_OPERATION_IN_PROGRESS,
                self::MAX_RETRY_COUNT,
                self::MAX_RETRY_DELAY_MILLIS - 500,
                self::MAX_RETRY_DELAY_MILLIS,
                true
            );
        }
    }

    public function get1ccPrepayCodConfig(): array
    {
        $currentCodIntelligenceConfigs = $this->merchant->get1ccConfig(Type::COD_INTELLIGENCE);

        $prepayConfigs = $this->merchant->get1ccConfig(Type::ONE_CC_PREPAY_COD_CONVERSION);

        if ($prepayConfigs != null && $prepayConfigs->getValueJson() != null)
        {
            $prepayConfigsJson = $prepayConfigs->getValueJson();
            $prepayConfigsFlag = $prepayConfigs->getValue() == '1';
            if ($currentCodIntelligenceConfigs != null && $currentCodIntelligenceConfigs->getValue() == '1')
            {
                $prepayConfigsJson[Constants::RISK_CATEGORY] = Constants::ALL_RISK_CATEGORIES;
            }
            return [
                Constants::ENABLED => $prepayConfigsFlag,
                Constants::CONFIGS => $prepayConfigsJson,
            ];
        }

        return [
            Constants::ENABLED => false,
            Constants::CONFIGS => null,
        ];
    }

    public function get1ccConfig($internal = false)
    {
        // Special handling for Shopify
        $merchantPlatformConfig = $this->merchant->getMerchantPlatformConfig();

        $configFlagsResponse = $this->get1ccConfigFlagsStatus($this->merchant, $internal);

        $domainUrlConfig = $this->merchant->get1ccConfig(Type::DOMAIN_URL);
        $domainUrl = null;
        if ($domainUrlConfig !== null)
        {
            $domainUrl = $domainUrlConfig->getValue();
        }

        if ($merchantPlatformConfig !== null and $merchantPlatformConfig->getValue() === Constants::SHOPIFY)
        {
            $codEngineTypeConfig = $this->merchant->get1ccConfig(Constants::COD_ENGINE_TYPE);
            $codEngineType = $codEngineTypeConfig !== null ? $codEngineTypeConfig->getValue() : null;

            $config = $this->repo->merchant_1cc_auth_configs->findByConfig(
                $this->merchant->getId(),
                Constants::SHOPIFY,
                Constants::SHOP_ID
            );

            $response = [
                "domain_url"      => $domainUrl,
                'platform'         => Constants::SHOPIFY,
                Constants::SHOP_ID => '',
                Constants::COD_ENGINE_TYPE => $codEngineType
            ];

            $response = array_merge($response, $configFlagsResponse);

            if ($config !== null)
            {
                $response[Constants::SHOP_ID] = $config->getValue();
            }
            return $response;
        }

        $shippingInfoUrlConfig = $this->merchant->getShippingInfoUrlConfig();
        $shippingInfoUrl = null;
        if ($shippingInfoUrlConfig !== null)
        {
            $shippingInfoUrl = $shippingInfoUrlConfig->getValue();
        }

        $couponsUrlConfig = $this->merchant->getFetchCouponsUrlConfig();
        $couponsUrl = null;
        if ($couponsUrlConfig !== null)
        {
            $couponsUrl = $couponsUrlConfig->getValue();
        }

        $applyCouponUrlConfig = $this->merchant->getApplyCouponUrlConfig();
        $applyCouponUrl = null;
        if ($applyCouponUrlConfig !== null)
        {
            $applyCouponUrl = $applyCouponUrlConfig->getValue();
        }

        $codSlabsConfig = $this->merchant->slab(\RZP\Models\Merchant\Slab\Type::COD_SLAB);
        $codSlabs = null;
        if ($codSlabsConfig !== null)
        {
            $codSlabs = $codSlabsConfig->getSlab();
        }

        $merchantPlatformConfig = $this->merchant->getMerchantPlatformConfig();
        $merchantPlatform = null;
        if ($merchantPlatformConfig !== null)
        {
            $merchantPlatform = $merchantPlatformConfig->getValue();
        }

        $couponConfigEntity = $this->merchant->get1ccConfig(Type::COUPON_CONFIG);
        $couponConfig = null;
        if ($couponConfigEntity !== null)
        {
            $couponConfig = $couponConfigEntity->getValueJson();
        }
        $result = [
            "domain_url"      => $domainUrl,
            "shipping_info"   => $shippingInfoUrl,
            "list_promotions" => $couponsUrl,
            "apply_promotion" => $applyCouponUrl,
            "cod_slabs"       => $codSlabs,
            "platform"        => $merchantPlatform,
            "coupon_config"   => $couponConfig
        ];

        if ($merchantPlatform === Constants::NATIVE)
        {
            $orderStatusUpdateUrlConfig = $this->merchant->getFetchOrderStatusUpdateUrlConfig();
            if ($orderStatusUpdateUrlConfig !== null)
            {
                $result[Constants::ORDER_STATUS_UPDATE_URL] = $orderStatusUpdateUrlConfig->getValue();
            }
        }

        foreach ($configFlagsResponse as $config => $value) {
            if (in_array($config, Constants::CONFIG_FLAGS) === true &&
                in_array($config, Constants::SHOPIFY_SPECIFIC_CONFIGS) === false) {
                $result[$config] = $value;
            }
        }

        return $result;
    }

    public function getCheckout1ccConfig(): array
    {
        return $this->get1ccConfigFlagsStatus($this->merchant);
    }

    public function getInternal1ccConfig($merchantId)
    {
        try
        {
            $this->merchant = $this->repo->merchant->findOrFail($merchantId);
        }
        catch (\Exception $ex)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_MERCHANT_ID);
        }

        $this->app['basicauth']->setMerchant($this->merchant);

        return $this->get1ccConfig(true);
    }

    /**
     * @throws BadRequestException
     */
    public function getInternal1ccPrepayCodConfig($merchantId): array
    {
        try
        {
            $this->merchant = $this->repo->merchant->findOrFail($merchantId);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::ERROR,
                TraceCode::MAGIC_GET_PREPAY_CONFIGS_FAILED);
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_MERCHANT_ID);
        }

        $this->app['basicauth']->setMerchant($this->merchant);

        $response =  $this->get1ccPrepayCodConfig();
        $platform = $this->merchant->getMerchantPlatformConfig();
        if ($platform !== null && isset($platform["value"]))
        {
            $response["platform"] = $platform["value"] ;
        }

        return $response;
    }

    /**
     * @throws \Exception
     */
    protected function reset1ccConfig($platform)
    {
        $merchantId = $this->merchant->getId();
        $configs = $this->repo->merchant_1cc_configs->findByMerchantId($merchantId)->getModels();

        foreach ($configs as $config)
        {
            $currentConfig = $config['config'];

            if ($this->shouldResetConfig($platform, $currentConfig) === true)
            {
                $config->delete();
            }

        }
        $slabs = $this->repo->merchant_slabs->findByMerchantId($merchantId)->getModels();
        foreach ($slabs as $slab)
        {
            $slab->delete();
        }
    }

    protected function shouldResetConfig(string $platform, string $config): bool
    {
        if ($platform === Constants::SHOPIFY && in_array($config, Constants::SHOPIFY_RESETTABLE_CONFIGS))
        {
            return true;
        }
        if ($platform !== Constants::SHOPIFY && in_array($config, Constants::NATIVE_RESETTABLE_CONFIGS) === true)
        {
            return true;
        }
        if (in_array($config, Constants::SHOPIFY_SPECIFIC_CONFIGS) === true)
        {
            return true;
        }
        if (in_array($config, Constants::GIFT_CARD_CONFIGS) === true)
        {
            return true;
        }
        return false;
    }

    public function getCODIntelligenceConfig(string $merchantId) : bool
    {
        $codIntelligenceConfig =  $this->repo->merchant_1cc_configs->
        findByMerchantAndConfigType($merchantId, Type::COD_INTELLIGENCE);
        return $codIntelligenceConfig !==  null && $codIntelligenceConfig->getValue() === "1";
    }

    public function getManualCODOrderReviewConfig(string $merchantId) : bool
    {
        $manualCodOrderReviewConfig =  $this->repo->merchant_1cc_configs->
        findByMerchantAndConfigType($merchantId, Type::MANUAL_CONTROL_COD_ORDER);
        return $manualCodOrderReviewConfig !==  null && $manualCodOrderReviewConfig->getValue() === "1";
    }

    private function add1ccConfigFlags($input, string $type)
    {
        if (in_array($type, Constants::CONFIG_FLAGS) === false) {
            return ;
        }

        $updatedConfig = isset($input[$type]) && $input[$type] === true;

        $config = $this->merchant->get1ccConfig($type);

        $currentConfig = $config !==  null && $config->getValue() === "1";

        if(($config == null)  || ($currentConfig !==  $updatedConfig))
        {
            (new Core)->associateMerchant1ccConfig($type,
                $updatedConfig
            );
        }
    }

    /**
     * @throws \Throwable
     */
    public function disable1ccMagicCheckout($input)
    {
        if (isset($input['platform']) == false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, 'Platform is required');
        }

        if ($input['platform'] !== Constants::SHOPIFY)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, 'Platform is invalid');
        }

        if ($input['platform'] === Constants::SHOPIFY)
        {
            (new Validator())->setStrictFalse()->validateInput(Constants::SHOPIFY, $input);
        }

        $this->repo->transaction(
            function () use ($input)
            {
                $this->repo->merchant_1cc_auth_configs->deleteByConfig(
                    $this->merchant->getId(),
                    Constants::SHOPIFY,
                    Constants::SHOP_ID
                );

                $shopId = (new ShopifyUtils)->stripAndReturnShopId($input[Constants::SHOP_ID]);

                $this->repo->merchant_1cc_auth_configs->create(
                    [
                        'merchant_id' => $this->merchant->getId(),
                        'platform'    => Constants::SHOPIFY,
                        'config'      => Constants::SHOP_ID,
                        'value'       => $shopId
                    ]
                );

                if (isset($input[Type::ONE_CLICK_CHECKOUT]) && $input[Type::ONE_CLICK_CHECKOUT] === false)
                {

                    $this->add1ccConfigFlags($input, Type::ONE_CLICK_CHECKOUT);

                    if (isset($input['reason']))
                    {
                            $reason = $input['reason'];

                            $flow = Constants::DISABLE_MAGIC_CHECKOUT;

                            (new Core())->associateMerchant1ccComments($flow, $reason);
                    }

                    if (isset($input['additional_reason']))
                    {
                        $reason = $input['additional_reason'];

                        $flow = Constants::DISABLE_MAGIC_CHECKOUT_ADDITIONAL_COMMENT;

                        (new Core())->associateMerchant1ccComments($flow, $reason);
                    }
                }
            }
        );
        $configOneClickCheckout = $this->merchant->get1ccConfig(Type::ONE_CLICK_CHECKOUT);
        $oneClickCheckoutValue = ($configOneClickCheckout !== null && $configOneClickCheckout->getValue() === "1") ? Constants::TRUE : Constants::FALSE;
        (new Merchant\OneClickCheckout\Shopify\Service())->controlMagicCheckout(Constants::ONE_CLICK_CHECKOUT_ENABLED,$oneClickCheckoutValue);
        $this->trace->info(
            TraceCode::MAGIC_CHECKOUT_DISABLED,
            [
                'merchant_id'=>$this->merchant->getId(),
                'MAGIC_CHECKOUT_VALUE'=> $oneClickCheckoutValue
            ]);

        $configBuyNow = $this->merchant->get1ccConfig(Type::ONE_CC_BUY_NOW_BUTTON);
        $buyNowValue = ($configBuyNow !==  null && $configBuyNow->getValue() === "1") ? Constants::TRUE : Constants::FALSE;
        $buyNowValue = $oneClickCheckoutValue === Constants::FALSE ? $oneClickCheckoutValue : $buyNowValue;
        (new Merchant\OneClickCheckout\Shopify\Service())->controlMagicCheckout(Constants::BUY_NOW_ENABLED,$buyNowValue);
        $this->trace->info(
            TraceCode::BUY_NOW_BUTTON_ENABLED_OR_DISABLED,
            [
                'merchant_id'=>$this->merchant->getId(),
                'BUY_NOW_ENABLED/DISABLED'=> $buyNowValue
            ]);

    }

    /**
     * Note: Any new configs added / returned from this method also need to be
     *       added into the proto files & transformer of checkout service.
     *
     * @param Merchant\Entity $merchant
     * @param bool            $internal
     *
     * @return array
     */
    public function get1ccConfigFlagsStatus(Merchant\Entity $merchant, $internal = false) {
        $response = [];
        $merchantId = $merchant->getId();
        $allConfigs = $this->repo->merchant_1cc_configs->findByMerchantId($merchantId)->getModels();
        $platform = null;

        /**
         *  Getting platform
         */
        foreach ($allConfigs as $config)
        {
            $configName = $config['config'];
            if ($configName === Constants::PLATFORM) {
                $platform = $config->getValue();
                break;
            }
        }

      /**
       * config flags which are not feature flags
       *  will have default value as false, except for fetch coupons
       */
       foreach (Constants::COMMON_CONFIGS as $flag)
       {
           $response[$flag] = false;
           if ($flag == Constants::ONE_CC_AUTO_FETCH_COUPONS) {
               $response[$flag] = true;
           }
       }

        /** config flags which are also feature flags
        *  will have default value of features if config
        *  not present
        */
        foreach (Constants::SHOPIFY_SPECIFIC_CONFIGS as $flag)
        {
            $response[$flag] = false;
            if (in_array($flag, Constants::CONFIG_CUM_FEATURE_FLAGS) === true) {
                $response[$flag] = $merchant->isFeatureEnabled($flag);
            }
        }

        /**
         * Gift card configs are not for native
         */
        if ($platform !== Constants::NATIVE) {
            foreach (Constants::GIFT_CARD_CONFIGS as $flag)
            {
               $response[$flag] = false;
            }
        }

        if ($internal)
        {
           foreach (Constants::INTERNAL_CONFIGS as $flag)
           {
               $featureStatus = $merchant->isFeatureEnabled($flag);
               $response[$flag] = $featureStatus;
           }
        }

        /**
         * Give config values if present
         */
        foreach ($allConfigs as $config)
        {
            $configName = $config['config'];
            $configValue = $config->getValue() === '1';

            if (in_array($configName, Constants::CONFIG_FLAGS) === true) {
                $response[$configName] =  $configValue;
            }
        }

        return $response;
    }

    /**
     * @param array $input
     * @throws \Throwable
     */
    // Since, this is a an internal auth route we don't have any
    // merchant/auth/mode. We take merchant_id and set merchant in basic auth.
    public function updateShippingProviderConfig(array $input)
    {
        (new Validator())->setStrictFalse()->validateInput('shippingProvider', $input);

        $this->merchant = $this->repo->merchant->findOrFail($input['merchant_id']);

        $this->app['basicauth']->setMerchant($this->merchant);

        unset($input['merchant_id']);

        return (new MerchantService)->updateShippingMethodProviderConfig($input)->getValueJson();
    }

    /**
     * @throws BadRequestException
     */
    public function getShopify1ccConfigs($input)
    {
        $this->trace->info(TraceCode::MERCHANT_1CC_CONFIGS_REQUESTED, [
            'input' => $input,
        ]);

        $result = [];

        if (isset($input['key_id']) === true)
        {
            $keyId = $input['key_id'];

            (new Validator())->setStrictFalse()->validateInput('gettingShopifyConfigByKeyId', $input);

            $this->getModeAndSetDBConnectionForConfigs($input);

            Key\Entity::verifyIdAndStripSign($keyId);

            $key = $this->repo->key->findOrFailPublic($keyId);

            $input[Constants::MERCHANT_ID] = $key->getMerchantId();

            $this->merchant = $this->repo->merchant->findOrFail($input[Constants::MERCHANT_ID]);

            $result['key_id'] = $input['key_id'];
        }
        else if (isset($input['shop_id']) === true)
        {
            (new Validator())->setStrictFalse()->validateInput('gettingShopifyConfigByShopId', $input);

            $this->getModeAndSetDBConnectionForConfigs($input);

            $shopId = $input['shop_id'];

            $merchantDetails = $this->repo->merchant_1cc_auth_configs->findLatestMerchantIdByPlatformConfigValue(
                $shopId, Constants::SHOPIFY, Constants::SHOP_ID);

            if ($merchantDetails === null)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, "Merchant Id not found");
            }

            $merchantId = $merchantDetails['merchant_id'];

            $this->merchant = $this->repo->merchant->findOrFail($merchantId);

            $input[Constants::MERCHANT_ID] = $merchantId;

        }
        else if (isset($input[Constants::MERCHANT_ID]) === true)
        {
            (new Validator())->setStrictFalse()->validateInput('gettingShopifyConfigByMerchantId', $input);

            $this->getModeAndSetDBConnectionForConfigs($input);

            $this->merchant = $this->repo->merchant->findOrFail($input[Constants::MERCHANT_ID]);
        }
        else
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, "request must contain any one of key_id, merchant_id or shop_id");
        }

        if (isset($result['key_id']) === false)
        {
            $key = $this->repo->key->getLatestActiveKeyForMerchant($input[Constants::MERCHANT_ID]);

            $result['key_id'] = $key->getPublicKey($input['mode']);
        }

        $result[Constants::MERCHANT_ID] = $input[Constants::MERCHANT_ID];

        if (empty($input[Constants::KEYS]) === true) {
            return $result;
        }

        $merchantAuthConfigs = (new Merchant\OneClickCheckout\AuthConfig\Core)->getShopify1ccConfig($this->merchant->getId());

        $merchantConfigs = $this->get1ccConfigFlagsStatus($this->merchant);

        $requestedKeys = explode(',', $input[Constants::KEYS]);

        foreach ($merchantConfigs as $config => $value)
        {
            if (in_array($config, $requestedKeys) === true) {
                $result[$config] = $value;
            }
        }

        foreach ($merchantAuthConfigs as $key => $value)
        {
            if (in_array($key, $requestedKeys) === true) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    protected function getModeAndSetDBConnectionForConfigs($input)
    {
        if (isset($input['key_id']) === true)
        {
            $keyId = $input['key_id'];
            $mode = substr($keyId, 4, 4);
        }
        else if (isset($input['mode']) === true)
        {
            $mode = $input['mode'];
        }
        else
        {
            if (env('APP_MODE', 'prod') === 'prod')
            {
                $mode = 'live';
            }
            else
            {
                $mode = 'test';
            }
        }

        $this->app['basicauth']->authCreds->setModeAndDbConnection($mode);
    }


    /**
     * @param $shippingProvider
     * @return void
     * @throws BadRequestException
     */
    protected function updateShippingInfoConfig($shippingProvider): void
    {
        if (empty($shippingProvider) !== true && empty($shippingProvider['url']) !== true) {
            $codFeeRule = [];
            $shippingFeeRule = [];
            if (empty($shippingProvider['cod_slabs']) !== true) {
                $codSlabs = (new MerchantService())->validateAndSortSlabs($shippingProvider['cod_slabs']);
                $codFeeRule = (new ShippingService())->covertToNewSlabFormat($codSlabs);
            }
            if (empty($shippingProvider['shipping_slabs']) !== true) {
                $shippingSlabs = (new MerchantService())->validateAndSortSlabs($shippingProvider['shipping_slabs']);
                $shippingFeeRule = (new ShippingService())->covertToNewSlabFormat($shippingSlabs);
            }
            $req = [
                'provider_type' => 'merchant',
                'provider_id' => $this->merchant->getId(),
                'merchant' => [
                    'url' => $shippingProvider['url'],
                ],
            ];
            if (empty($codFeeRule) === false) {
                $req['cod_fee_rule'] = $codFeeRule;
            }
            if (empty($shippingFeeRule) === false) {
                $req['shipping_fee_rule'] = $shippingFeeRule;
            }
            $this->mutex->acquireAndRelease(
                self::MUTEX_KEY . ':' . $this->merchant->getId() . ':' . Type::SHIPPING_INFO_URL,
                function () use ($req) {
                    $this->app['shipping_provider_service']->create($req, $this->merchant->getId());

                },
                self::MUTEX_LOCK_TTL_SEC,
                ErrorCode::BAD_REQUEST_ANOTHER_1CC_CONFIG_OPERATION_IN_PROGRESS,
                self::MAX_RETRY_COUNT,
                self::MAX_RETRY_DELAY_MILLIS - 500,
                self::MAX_RETRY_DELAY_MILLIS,
                true
            );
        }
    }

    /**
     * Returns methods and offers for the merchant
     * Currently used for branded button flow
     * @throws BadRequestException
     */
    public function getMethodsAndOffersForMerchant(array $input) : array
    {
        if ($this->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::ONE_CLICK_CHECKOUT) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $amount = $input['amount'] ?? 0;
        $merchantId = $this->merchant->getId();
        $offerData = $this->app['cache']->get($this->getMerchantBasedCacheKey(self::MERCHANT_METHODS_OFFER_MUTEX_KEY_PREFIX, 'offers'));
        if (empty($offerData) === true)
        {
            $allOffers = $this->repo->offer->fetchAllActiveNonSubscriptionOffers($merchantId);
            $offers = [];
            foreach($allOffers as $offer)
            {
                if ($offer->isDefaultOffer() || $offer->getCheckoutDisplay() === 1)
                {
                    $offers[] = $offer;
                }
            }
            $offerData = [];
            foreach ($offers as $offer)
            {
                $offerData[] = [
                    'min_amount' => $offer->getMinAmount(),
                    'payment_method' => $offer->getPaymentMethod(),
                ];
            }

            $this->app['cache']->set(
                $this->getMerchantBasedCacheKey(
                    self::MERCHANT_METHODS_OFFER_MUTEX_KEY_PREFIX,
                    'offers'), $offerData, self::CACHE_TTL
            );
        }
        $offerMethods = [];

        foreach ($offerData as $offer)
        {
            if ($offer['min_amount'] > $amount)
            {
                continue;
            }

            $method = $offer['payment_method'];
            if (empty($method) === true)
            {
                continue;
            }
            $offerMethods[$method] = true;
        }

        $methods = $this->app['cache']->get($this->getMerchantBasedCacheKey(self::MERCHANT_METHODS_OFFER_MUTEX_KEY_PREFIX, 'methods'));
        if (empty($methods) === true)
        {
            $methodsEntity = $this->repo->methods->getMethodsForMerchant($this->merchant);

            $walletMap = $methodsEntity->getEnabledWallets();
            $wallet = [];
            foreach ($walletMap as $key => $value)
            {
                if ($value === true)
                {
                    $wallet[] = $key;
                }
            }

            $paylaterMap = $methodsEntity->getEnabledPaylaterProviders();
            $paylater = [];
            foreach ($paylaterMap as $key => $value)
            {
                if ($value === true)
                {
                    $paylater[] = $key;
                }
            }
            $methods = [
                'upi' => $methodsEntity->isUpiEnabled(),
                'card' => $methodsEntity->isCardEnabled(),
                'netbanking' => $methodsEntity->isNetbankingEnabled(),
                'wallet' => $wallet,
                'paylater' => $paylater,
                'cod' => $methodsEntity->isCodEnabled(),
                'cardless_emi' => $methodsEntity->isCardlessEmiEnabled(),
            ];

            $this->app['cache']->set(
                $this->getMerchantBasedCacheKey(
                    self::MERCHANT_METHODS_OFFER_MUTEX_KEY_PREFIX,
                    'methods'), $methods, self::CACHE_TTL
            );
        }

        $enabled = $this->brandedButtonExperiment();

        return [
            'enabled' => $enabled,
            'methods' => $methods,
            'offer_methods'  => $offerMethods,
        ];
    }

    protected function getMerchantBasedCacheKey($prefix, $key): string
    {
        return $prefix . ':' . $this->merchant->getId() . ':' . $key;
    }

    protected function brandedButtonExperiment(): bool
    {
        $properties = [
            'id'            => UniqueIdEntity::generateUniqueId(),
            'experiment_id' => $this->app['config']->get('app.1cc_branded_btn_splitz_exp_id'),
            'request_data'  => json_encode(
                [
                    'merchant_id' =>  $this->merchant->getId(),
                ]),
        ];

        $response = $this->app['splitzService']->evaluateRequest($properties);
        $res = $response['response']['variant']['name'] ?? null;
        return $res === 'enabled';
    }

    public function get1ccAddressIngestionConfig($input): array
    {
        (new Validator())->setStrictFalse()->validateInput('get1ccAddressIngestionConfig', $input);

        $configs = [];

        $keys = [];

        if (isset($input['keys']))
        {
            $keys = $input['keys'];
        }

        if (sizeof($keys) == 0 || in_array(Constants::ONE_CLICK_CHECKOUT, $keys))
        {
            $feature = $this->repo->feature->findByEntityTypeEntityIdAndName(
                'merchant',
                $this->merchant->getId(),
                Constants::ONE_CLICK_CHECKOUT
            );

            if (!is_null($feature))
            {
                $config = $this->merchant->get1ccConfig(Constants::ONE_CLICK_CHECKOUT);
                if (is_null($config))
                {
                    $configs[Constants::ONE_CLICK_CHECKOUT] = true;
                }
                else
                {
                    $configs[Constants::ONE_CLICK_CHECKOUT] = $config->getValue() === "1";
                }
                $configs[Constants::ONE_CC_ONBOARDED_TIMESTAMP] = $feature->getCreatedAt();
            }
            else
            {
                $configs[Constants::ONE_CLICK_CHECKOUT] = false;
            }
        }

        if (sizeof($keys) == 0 || in_array(Constants::ONE_CC_ADDRESS_SYNC_OFF, $keys))
        {
            $configs[Constants::ONE_CC_ADDRESS_SYNC_OFF] = $this->merchant->isFeatureEnabled(Constants::ONE_CC_ADDRESS_SYNC_OFF);
        }

        if (sizeof($keys) == 0 || in_array(Constants::ONE_CC_ADDRESS_INGESTION_JOB, $keys))
        {
            unset($input['keys']);
            $configs[Constants::ONE_CC_ADDRESS_INGESTION_JOB] = $this->app['magic_address_provider_service']->getJobConfig($input, $this->merchant->getId());
        }

        return $configs;
    }

    /**
     * @throws ServerErrorException
     * @throws BadRequestException
     */
    public function adminWhitelistCoupons(string $merchantId, $input): array
    {
        $this->merchant = $this->repo->merchant->find($merchantId);

        if ($this->merchant === null)
        {
            return [
                [
                    "Code"=> "BAD_REQUEST_INVALID_MERCHANT_ID",
                    "Description"=> "Invalid merchant ID",
                    "Field"=> "",
                    "Source"=> "",
                    "Step"=> "",
                    "Reason"=> "input_validation_failed",
                ],
                200
            ];
        }

        $this->app['basicauth']->setMerchant($this->merchant);

        $isWhitelistingEnabled = (new Merchant\Merchant1ccConfig\Core())->get1ccConfigByMerchantIdAndType($merchantId, "one_cc_whitelist_coupons");
        if ($isWhitelistingEnabled === null or $isWhitelistingEnabled->getValue() != 1)
        {
            (new Merchant\Core)->associateMerchant1ccConfig(
                "one_cc_whitelist_coupons",
                true
            );
        }

        $key = $this->repo->key->getKeysForMerchant($this->merchant->getId())->first();

        $res = $this->app['integration_service_client']->makeMultipartRequest(
            self::WHITELIST_COUPONS_UPLOAD_PATH . $key->getPublicKey(),
            $input["data"],
        );

        if($res->getStatusCode() != 200 && $res->getStatusCode() != 400)
        {
            throw new ServerErrorException('Received unexpected response from consumer app',
                ErrorCode::SERVER_ERROR);
        }
        $response = json_decode($res->getBody(), true);
        return [$response, 200];
    }

    public function getWoocommerce1ccConfigs($input)
    {
        (new Validator())->setStrictFalse()->validateInput('gettingWoocommerceConfig', $input);

        $mode = $this->getModeForConfigs($input);
        $this->app['basicauth']->authCreds->setModeAndDbConnection($mode);

        $merchantId = $input[Constants::MERCHANT_ID];
        $this->merchant = $this->repo->merchant->findOrFail($merchantId);
        $result[Constants::MERCHANT_ID] = $merchantId;

        if (empty($input[Constants::KEYS]) === true)
        {
            return $result;
        }

        $merchantAuthConfigs = (new Merchant\OneClickCheckout\AuthConfig\Core)->
        ge1ccAuthConfigsByMerchantIdAndPlatform($merchantId, Constants::WOOCOMMERCE);

        $requestedKeys = explode(',', $input[Constants::KEYS]);
        if (in_array(Constants::DOMAIN_URL, $requestedKeys) === true)
        {
            $domainUrlConfig = $this->merchant->get1ccConfig(Constants::DOMAIN_URL);
            $result[Constants::DOMAIN_URL] = $domainUrlConfig != null ? $domainUrlConfig->getValue() : "";
        }

        foreach ($merchantAuthConfigs as $key => $value)
        {
            if (in_array($key, $requestedKeys) === true)
            {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
