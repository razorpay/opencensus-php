<?php

namespace RZP\Models\Merchant\OneClickCheckout\AuthConfig;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\OneClickCheckout\Constants;
use RZP\Models\Merchant\OneClickCheckout\Shopify;
use RZP\Models\Merchant\OneClickCheckout\MagicCheckoutProvider\MerchantProvider;

class Service extends Base\Service
{
    const SHOPIFY_UPDATE_AUTH_CONFIG = 'shopify_update_auth_config';
    const SHOPIFY_CREATE_ACCOUNT     = 'shopify_create_account';

    // updateShopify1ccConfig is used to update the access tokens locally in API monolith or create an account in
    // the new microservice based on the "action" flag passed. This is a stop gap solution and the API will be depreacated
    // and functionality moved to Rzp admin dashboard.
    public function updateShopify1ccConfig($input): array
    {
        $action = $input['action'] ?? self::SHOPIFY_UPDATE_AUTH_CONFIG;
        $resp = ['action' => $action];

        if ($action === self::SHOPIFY_UPDATE_AUTH_CONFIG)
        {
            $this->logUpdateConfig($action, 'updating local configs');
            (new Validator)->setStrictFalse()->validateInput('updateShopifyConfig', $input);
            $output = (new Core)->updateShopify1ccConfig($input);
            $resp = array_merge($resp, $output);
        }
        else if ($action === self::SHOPIFY_CREATE_ACCOUNT)
        {
            $this->logUpdateConfig($action, 'creating account in microservice');
            // validator is part of the below function.
            $output = (new Shopify\Onboarding)->createNewShopifyAccount($input);
            $resp = array_merge($resp, $output);
        }
        else
        {
            $this->logUpdateConfig($action, 'config update failed');
            throw new Exception\BadRequestValidationFailureException(
                'invalid action received'
            );
        }
        return $resp;
    }

    protected function logUpdateConfig(string $action, string $message)
    {
        $this->trace->info(
            TraceCode::SHOPIFY_1CC_UPDATE_CONFIG,
            ['action' => $action, 'message' => $message]
        );
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
