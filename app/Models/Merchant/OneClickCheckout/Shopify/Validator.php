<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $getPromotionsRules = [
        Entity::MERCHANT_ID               => 'required|string|size:14',
        Entity::SHOP_ID                   => 'required|string',
        Entity::API_KEY                   => 'required|string',
        Entity::API_SECRET                => 'required|string',
        // Entity::OAUTH_TOKEN               => 'maybe|string',
        Entity::STOREFRONT_ACCESS_TOKEN   => 'required|string',
    ];

    protected static $applyPromotionRules = [
        Entity::MERCHANT_ID               => 'required|string|size:14',
        Entity::SHOP_ID                   => 'required|string',
        Entity::API_KEY                   => 'required|string',
        Entity::API_SECRET                => 'required|string',
        // Entity::OAUTH_TOKEN               => 'maybe|string',
        Entity::STOREFRONT_ACCESS_TOKEN   => 'required|string',
    ];

    protected static $shopifyCreateCheckoutRules = [
        Entity::MERCHANT_ID               => 'required|string|size:14',
        Entity::SHOP_ID                   => 'required|string',
        Entity::API_KEY                   => 'required|string',
        Entity::API_SECRET                => 'required|string',
        // Entity::OAUTH_TOKEN               => 'maybe|string',
        Entity::STOREFRONT_ACCESS_TOKEN   => 'required|string',
    ];

    protected static $shopifyCompleteCheckoutRules = [
        Entity::MERCHANT_ID               => 'required|string|size:14',
        Entity::CART                      => 'required|array',
    ];

    // Keep for now, probably not required as we can't always rely on Shopify API changes
    protected static $shopifyOAuthRedirectRules = [
        Entity::MERCHANT_ID               => 'required|string|size:14',
        Entity::CART                      => 'required|array',
    ];
}
