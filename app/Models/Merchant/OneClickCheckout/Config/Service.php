<?php

namespace RZP\Models\Merchant\OneClickCheckout\Config;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Core;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Merchant1ccConfig\Type;
use RZP\Models\Merchant\OneClickCheckout\Constants;
use RZP\Models\Merchant\OneClickCheckout\Shopify\Utils as ShopifyUtils;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
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
                    if ($merchantPlatform === Constants::SHOPIFY)
                    {
                        $this->repo->merchant_1cc_auth_configs->deleteByMerchantAndPlatform(
                            $this->merchant->getId(),
                            Constants::SHOPIFY
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

                if ($updatePlatform !== Constants::SHOPIFY)
                {
                    foreach ($input as $key => $value)
                    {
                        switch ($key)
                        {
                            case "shipping_info":
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
                                (new \RZP\Models\Merchant\Service())->updateShippingSlabs(['slabs' => $value]);
                                break;
                            case "cod_slabs":
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
                }

                $updatedCodIntelligenceEnabledFlag = isset($input[Type::COD_INTELLIGENCE]) &&
                    $input[Type::COD_INTELLIGENCE] === true;

                $currentCodIntelligenceEnabledFlag = $this->merchant->getCODIntelligenceConfig();

                if(($reset === true ) || ($currentCodIntelligenceEnabledFlag !== $updatedCodIntelligenceEnabledFlag))
                {
                    (new Core)->associateMerchant1ccConfig(
                        Type::COD_INTELLIGENCE,
                        $updatedCodIntelligenceEnabledFlag
                    );
                }

                foreach ($input as $key => $value)
                {
                    switch ($key)
                    {
                        case "one_click_checkout":
                            if($updatePlatform === Constants::SHOPIFY) {
                                $this->add1ccConfigFlags($input, Type::ONE_CLICK_CHECKOUT);
                            }
                            break;
                        case "one_cc_buy_now_button":
                            if($updatePlatform === Constants::SHOPIFY) {
                                $this->add1ccConfigFlags($input, Type::ONE_CC_BUY_NOW_BUTTON);
                            }
                        case "one_cc_ga_analytics":
                            if($updatePlatform === Constants::SHOPIFY) {
                                $this->add1ccConfigFlags($input, Type::ONE_CC_GA_ANALYTICS);
                            }
                            break;
                        case "one_cc_fb_analytics":
                            if($updatePlatform === Constants::SHOPIFY) {
                                $this->add1ccConfigFlags($input, Type::ONE_CC_FB_ANALYTICS);
                            }
                            break;
                        case "one_cc_auto_fetch_coupons":
                            $this->add1ccConfigFlags($input, Type::ONE_CC_AUTO_FETCH_COUPONS);
                            break;
                        case "one_cc_international_shipping":
                            $this->add1ccConfigFlags($input, Type::ONE_CC_INTERNATIONAL_SHIPPING);
                            break;
                        case "one_cc_capture_billing_address":
                            $this->add1ccConfigFlags($input, Type::ONE_CC_CAPTURE_BILLING_ADDRESS);
                            break;
                    }
                }
            }
        );

        if ( $input['platform'] === Constants::SHOPIFY && (isset($input[Type::ONE_CLICK_CHECKOUT]) || isset($input[Type::ONE_CC_BUY_NOW_BUTTON]))) {

            $configOneClickCheckout = $this->merchant->get1ccConfig(Type::ONE_CLICK_CHECKOUT);
            $oneClickCheckoutValue = ($configOneClickCheckout !== null && $configOneClickCheckout->getValue() === "1") ? Constants::TRUE : Constants::FALSE;
            (new Merchant\OneClickCheckout\Shopify\Service())->controlMagicCheckout(Constants::ONE_CLICK_CHECKOUT_ENABLED, $oneClickCheckoutValue);
            $this->trace->info(
                TraceCode::MAGIC_CHECKOUT_ENABLED,
                [
                    'merchant_id' => $this->merchant->getId(),
                    'MAGIC_CHECKOUT_VALUE' => $oneClickCheckoutValue
                ]);

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

    public function get1ccConfig()
    {
        // Special handling for Shopify
        $merchantPlatformConfig = $this->merchant->getMerchantPlatformConfig();

        $configFlagsResponse = $this->get1ccConfigFlagsStatus($this->merchant);

        if ($merchantPlatformConfig !== null and $merchantPlatformConfig->getValue() === Constants::SHOPIFY)
        {
            $config = $this->repo->merchant_1cc_auth_configs->findByConfig(
                $this->merchant->getId(),
                Constants::SHOPIFY,
                Constants::SHOP_ID
            );

            $response = [
                'platform'         => Constants::SHOPIFY,
                Constants::SHOP_ID => ''
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

        return [
            "shipping_info"   => $shippingInfoUrl,
            "list_promotions" => $couponsUrl,
            "apply_promotion" => $applyCouponUrl,
            "cod_slabs"       => $codSlabs,
            "platform"        => $merchantPlatform,
            Constants::COD_INTELLIGENCE => $configFlagsResponse[Constants::COD_INTELLIGENCE],
            Constants::ONE_CC_AUTO_FETCH_COUPONS => $configFlagsResponse[Constants::ONE_CC_AUTO_FETCH_COUPONS],
            Constants::ONE_CC_INTERNATIONAL_SHIPPING => $configFlagsResponse[Constants::ONE_CC_INTERNATIONAL_SHIPPING],
            Constants::ONE_CC_CAPTURE_BILLING_ADDRESS => $configFlagsResponse[Constants::ONE_CC_CAPTURE_BILLING_ADDRESS]
        ];
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
            if (($platform === Constants::SHOPIFY && in_array($currentConfig, Constants::SHOPIFY_RESETTABLE_CONFIGS) === true)
             || ($platform !== Constants::SHOPIFY && in_array($currentConfig, Constants::NATIVE_RESETTABLE_CONFIGS) === true)) {
                $config->delete();
            }
        }
        $slabs = $this->repo->merchant_slabs->findByMerchantId($merchantId)->getModels();
        foreach ($slabs as $slab)
        {
            $slab->delete();
        }
    }

    public function getCODIntelligenceConfig(string $merchantId) : bool
    {
        $codIntelligenceConfig =  $this->repo->merchant_1cc_configs->
        findByMerchantAndConfigType($merchantId, Type::COD_INTELLIGENCE);
        return $codIntelligenceConfig !==  null && $codIntelligenceConfig->getValue() === "1";
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


    public function get1ccConfigFlagsStatus(Merchant\Entity $merchant) {
        $response = [];

        foreach(Constants::CONFIG_CUM_FEATURE_FLAGS as $flag)
        {
            $storedConfig = $merchant->get1ccConfig($flag);
            $featureStatus = $merchant->isFeatureEnabled($flag);
            if ($storedConfig !== null)
            {
                $featureStatus = $storedConfig->getValue() === "1";
            }
            $response[$flag] = $featureStatus;
        }

        foreach (Constants::CONFIG_FLAGS_ACROSS_ALL_PLATFORMS as $flag)
        {
            $configStatus = $merchant->get1ccConfigFlagStatus($flag);
            $response[$flag] = $configStatus;
        }

        // If Config not present then by default the value should be true.
        $autoFetchCouponsConfig = $merchant->get1ccConfig(Constants::ONE_CC_AUTO_FETCH_COUPONS);
        $autoFetchCouponsConfigStatus = true;
        if ($autoFetchCouponsConfig !== null)
        {
            $autoFetchCouponsConfigStatus = $autoFetchCouponsConfig->getValue() === "1";
        }

        $oneClickBuyNowConfigStatus = $merchant->get1ccConfigFlagStatus(Constants::ONE_CC_BUY_NOW_BUTTON);

        $response[Constants::ONE_CC_AUTO_FETCH_COUPONS]  = $autoFetchCouponsConfigStatus;
        $response[Constants::ONE_CC_BUY_NOW_BUTTON]      = $oneClickBuyNowConfigStatus;

        return $response;
    }
}
