<?php

namespace RZP\Models\Merchant\OneClickCheckout\Config;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Core;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Merchant1ccConfig\Type;
use RZP\Models\Merchant\OneClickCheckout\Constants;

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

                    $this->reset1ccConfig();
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


                    $this->repo->merchant_1cc_auth_configs->create(
                        [
                            'merchant_id' => $this->merchant->getId(),
                            'platform'    => Constants::SHOPIFY,
                            'config'      => Constants::SHOP_ID,
                            'value'       => $input[Constants::SHOP_ID],
                        ]
                    );
                }
            }
        );
    }

    public function get1ccConfig()
    {
        // Special handling for Shopify
        $merchantPlatformConfig = $this->merchant->getMerchantPlatformConfig();
        if ($merchantPlatformConfig !== null and $merchantPlatformConfig->getValue() === Constants::SHOPIFY)
        {
            $config = $this->repo->merchant_1cc_auth_configs->findByConfig(
                $this->merchant->getId(),
                Constants::SHOPIFY,
                Constants::SHOP_ID
            );
            if ($config !== null)
            {
                return [
                    'platform'         => Constants::SHOPIFY,
                    Constants::SHOP_ID => $config->getValue(),
                ];
            }
            else
            {
                return [
                    'platform'         => Constants::SHOPIFY,
                    Constants::SHOP_ID => '',
                ];
            }
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
        ];
    }

    /**
     * @throws \Exception
     */
    protected function reset1ccConfig()
    {
        $merchantId = $this->merchant->getId();
        $configs = $this->repo->merchant_1cc_configs->findByMerchantId($merchantId)->getModels();
        foreach ($configs as $config)
        {
            $config->delete();
        }
        $slabs = $this->repo->merchant_slabs->findByMerchantId($merchantId)->getModels();
        foreach ($slabs as $slab)
        {
            $slab->delete();
        }
    }

}
