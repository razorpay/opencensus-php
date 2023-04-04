<?php

namespace RZP\Models\Merchant\OneClickCheckout\AuthConfig;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\OneClickCheckout\Constants;

class Core extends Base\Core
{
    public function updateShopify1ccConfig(array $input)
    {
        $encryptedInput = array();

        $merchantId = $input['merchant_id'];

        unset($input['merchant_id']);

        foreach ($input as $key => $value)
        {

            if (in_array($key, Constants::SHOPIFY_AUTH_ENCRYPT)) {

                $value = $this->app['encrypter']->encrypt($value);
            }

            $encryptedInput[$key] = $value;
        }

        $this->transaction(
            function () use ($encryptedInput, $merchantId)
            {
                $recordsDeleted = $this->repo->merchant_1cc_auth_configs->deleteByMerchantAndPlatform(
                    $merchantId,
                    Constants::SHOPIFY
                );

                foreach ($encryptedInput as $key => $value)
                {
                    if (in_array($key, Constants::SHOPIFY_AUTH) === true)
                    {
                        $this->createAndSaveConfig($merchantId, Constants::SHOPIFY, $key, $value);
                    }
                }
            }
        );
        return ['success' => true];
    }

    public function updateWoocommerce1ccAuthConfig(array $input)
    {
        $encryptedInput = array();

        $merchantId = $input['merchant_id'];

        unset($input['merchant_id']);

        foreach ($input as $key => $value)
        {

            if (in_array($key, Constants::WOOCOMMERCE_AUTH_ENCRYPT)) {

                $value = $this->app['encrypter']->encrypt($value);
            }

            $encryptedInput[$key] = $value;
        }

        $this->transaction(
            function () use ($encryptedInput, $merchantId)
            {
                $recordsDeleted = $this->repo->merchant_1cc_auth_configs->deleteByMerchantAndPlatform(
                    $merchantId,
                    Constants::WOOCOMMERCE
                );

                foreach ($encryptedInput as $key => $value)
                {
                    if (in_array($key, Constants::WOOCOMMERCE_AUTH) === true)
                    {
                        $this->createAndSaveConfig($merchantId, Constants::WOOCOMMERCE, $key, $value);
                    }
                }
            }
        );
    }

    public function updateNative1ccAuthConfig(array $input)
    {
        $encryptedInput = array();

        $merchantId = $input['merchant_id'];

        unset($input['merchant_id']);

        foreach ($input as $key => $value)
        {

            if (in_array($key, Constants::NATIVE_AUTH_ENCRYPT)) {

                $value = $this->app['encrypter']->encrypt($value);
            }

            $encryptedInput[$key] = $value;
        }

        $this->transaction(
            function () use ($encryptedInput, $merchantId)
            {
                $recordsDeleted = $this->repo->merchant_1cc_auth_configs->deleteByMerchantAndPlatform(
                    $merchantId,
                    Constants::NATIVE
                );

                foreach ($encryptedInput as $key => $value)
                {
                    if (in_array($key, Constants::NATIVE_AUTH) === true)
                    {
                        $this->createAndSaveConfig($merchantId, Constants::NATIVE, $key, $value);
                    }
                }
            }
        );
    }

    protected function createAndSaveConfig($merchantId, $platform, $config, $value)
    {
        $input = [
            Entity::MERCHANT_ID => $merchantId,
            Entity::PLATFORM => $platform,
            Entity::CONFIG => $config,
            Entity::VALUE => $value,
        ];

        $config = (new Entity)->build($input);

        $config->generateId();

        $this->repo->merchant_1cc_auth_configs->saveOrFail($config);
    }

    public function getShopify1ccConfig($merchantId)
    {
        $res = $this->repo->merchant_1cc_auth_configs->findByMerchantAndPlatform(
            $merchantId,
            Constants::SHOPIFY
        );

        $val = array();

        foreach ($res as $object)
        {
            $val[$object->getConfig()] = $object->getValue();
        }

        return $val;
    }

    public function ge1ccAuthConfigsByMerchantIdAndPlatform(string $merchantId,string $platform)
    {
        $res = $this->repo->merchant_1cc_auth_configs->findByMerchantAndPlatform(
            $merchantId,
            $platform
        );

        $val = array();

        foreach ($res as $object)
        {
            $val[$object->getConfig()] = $object->getValue();
        }

        return $val;
    }

    public function getShopify1ccConfigByShopId(string $shopId): array
    {
        $midResponse = $this->repo->merchant_1cc_auth_configs->findMerchantIdByPlatformConfigValue(
          $shopId, Constants::SHOPIFY, Constants::SHOP_ID);

        if ($midResponse === null)
        {
            return [];
        }

        $creds = $this->getShopify1ccConfig($midResponse['merchant_id']);
        $creds['merchant_id'] = $midResponse['merchant_id'];
        return $creds;
    }
}
