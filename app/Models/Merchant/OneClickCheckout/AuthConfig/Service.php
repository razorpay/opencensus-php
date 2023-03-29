<?php

namespace RZP\Models\Merchant\OneClickCheckout\AuthConfig;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\OneClickCheckout\Constants;
use RZP\Models\Merchant\OneClickCheckout\MagicCheckoutProvider\MerchantProvider;

class Service extends Base\Service
{
    public function updateShopify1ccConfig($input)
    {
        (new Validator)->validateInput('updateShopifyConfig', $input);
        (new Core)->updateShopify1ccConfig($input);
        return;
    }

    public function updateWoocommerce1ccAuthConfig($input)
    {
        (new Validator)->validateInput('updateWoocommerceConfig', $input);
        (new Core)->updateWoocommerce1ccAuthConfig($input);
        return;
    }

    public function updateNative1ccAuthConfig($input)
    {
        (new Validator)->validateInput('updateNativeConfig', $input);
        (new Core)->updateNative1ccAuthConfig($input);
        return;
    }

    /**
    * updateShopify1ccCredentials is used to sync credentials from Magic Checkout Service as a dual write.
    * @throws \Throwable
    * @throws Exception\ServerErrorException
    */
    public function updateShopify1ccCredentials($input)
    {
        try
        {
            (new Validator)->validateInput('shopifyCredentials', $input);
        }
        catch (\Throwable $e)
        {
            $log = ['keys' => array_keys($input), 'error' => $e->getMessage()];
            if (empty($input[Constants::MERCHANT_ID]) === false)
            {
                $log[Constants::MERCHANT_ID] = $input[Constants::MERCHANT_ID];
            }
            if (empty($input[Constants::SHOP_ID]) === false)
            {
                $log[Constants::SHOP_ID] = $input[Constants::SHOP_ID];
            }
            $this->trace->error(TraceCode::SHOPIFY_1CC_SYNC_CREDENTIALS_FAILED, $log);
            throw $e;
        }
        try
        {
            $body = [
                Entity::MERCHANT_ID                 => $input[Constants::MERCHANT_ID],
                Constants::SHOP_ID                  => $input[Constants::SHOP_ID],
                Constants::API_KEY                  => $input[Constants::CLIENT_ID],
                Constants::API_SECRET               => $input[Constants::CLIENT_SECRET],
                Constants::OAUTH_TOKEN              => $input[Constants::ADMIN_ACCESS_TOKEN],
                Constants::STOREFRONT_ACCESS_TOKEN  => $input[Constants::STOREFRONT_ACCESS_TOKEN],
            ];

            if (empty($input[Constants::DELEGATE_ACCESS_TOKEN]) === false)
            {
                $body[Constants::DELEGATE_ACCESS_TOKEN] = $input[Constants::DELEGATE_ACCESS_TOKEN];
            }

            $this->updateShopify1ccConfig($body);

            return [
                Entity::MERCHANT_ID => $body[Constants::MERCHANT_ID],
                Constants::SHOP_ID  => $body[Constants::SHOP_ID],
            ];
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_SYNC_CREDENTIALS_FAILED,
                [
                    'merchant_id' => $input[Constants::MERCHANT_ID],
                    'shop_id'     => $input[Constants::SHOP_ID],
                    'error'       => $e->getMessage(),
                ]);

            throw new Exception\ServerErrorException(
                'Error while updating Shopify Credentials',
                ErrorCode::SERVER_ERROR_SYNC_SHOPIFY_CREDENTIAL
            );
        }

    }
}
