<?php

namespace RZP\Models\Merchant\OneClickCheckout\AuthConfig;

use RZP\Base;
use RZP\Models\Merchant\OneClickCheckout\Constants;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID     => 'sometimes|max:14',
        Entity::PLATFORM        => 'required|max:50',
        Entity::CONFIG          => 'required|max:50',
        Entity::VALUE           => 'required|max:1000',
    ];

    protected static $updateShopifyConfigRules = [
        Entity::MERCHANT_ID                 => 'required|size:14',
        Constants::SHOP_ID                  => 'required|max:255',
        Constants::API_KEY                  => 'required|max:512',
        Constants::API_SECRET               => 'required|max:512',
        Constants::OAUTH_TOKEN              => 'required|max:512',
        Constants::STOREFRONT_ACCESS_TOKEN  => 'required|max:512',
        Constants::DELEGATE_ACCESS_TOKEN    => 'sometimes|max:512'
    ];

    protected static $updateWoocommerceConfigRules = [
        Entity::MERCHANT_ID                 => 'required|size:14',
        Constants::API_KEY                  => 'required|max:255',
        Constants::API_SECRET               => 'required|max:255'
    ];

    protected static $updateNativeConfigRules = [
        Entity::MERCHANT_ID                 => 'required|size:14',
        Constants::USERNAME                 => 'required|max:255',
        Constants::PASSWORD                 => 'required|max:255'
    ];

    protected static $shopifyCredentialsRules = [
        Entity::MERCHANT_ID                 => 'required|size:14',
        Constants::SHOP_ID                  => 'required|max:255',
        Constants::CLIENT_SECRET            => 'required|max:512',
        Constants::CLIENT_ID                => 'required|max:512',
        Constants::ADMIN_ACCESS_TOKEN       => 'required|max:512',
        Constants::STOREFRONT_ACCESS_TOKEN  => 'required|max:512',
        Constants::DELEGATE_ACCESS_TOKEN    => 'sometimes|max:512'
    ];

    // NOTE: Decide whether we keep `.myshopify.com` in the value or not
    protected function sanitizeShopifyShopId($name)
    {
        return $name;
    }
}
