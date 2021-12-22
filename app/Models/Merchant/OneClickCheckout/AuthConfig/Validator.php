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
        Entity::MERCHANT_ID                 => 'required|max:14',
        Constants::SHOP_ID                  => 'required|max:255',
        Constants::API_KEY                  => 'required|max:255',
        Constants::API_SECRET               => 'required|max:255',
        Constants::OAUTH_TOKEN              => 'required|max:255',
        Constants::STOREFRONT_ACCESS_TOKEN  => 'required|max:255'
    ];

    // NOTE: Decide whether we keep `.myshopify.com` in the value or not
    protected function sanitizeShopifyShopId($name)
    {
        return $name;
    }
}
