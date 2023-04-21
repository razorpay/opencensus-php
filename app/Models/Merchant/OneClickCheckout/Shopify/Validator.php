<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use RZP\Base;
use RZP\Models\Merchant\OneClickCheckout\Constants as Constants;

class Validator extends Base\Validator
{
    const UPDATE_CHECKOUT = 'update_checkout';
    const CREATE_CHECKOUT = 'create_checkout';
    const COMPLETE_CHECKOUT = 'complete_checkout';

    protected static $createCheckoutRules = [
      Constants::ORDER_ID => 'required|string|size:20',
      Constants::ORDER_ID => 'required|string|size:20',
    ];

    protected static $updateCheckoutRules = [
        Constants::ORDER_ID => 'required|string|size:20',
    ];

    protected static $completeCheckoutRules = [
      'razorpay_order_id'   => 'required|string|size:20',
      'razorpay_payment_id' => 'required|string|size:18',
    ];

    protected static $createShopifyOrderAndPreferencesRules = [
        'merchant_id'                  => 'required|string|size:14',
        'checkout'                     => 'required|array',
        'checkout.id'                  => 'required|string',
        'checkout.totalPrice'          => 'required|array',
        'checkout.totalPrice.amount'   => 'required',
        'checkout.lineItems'           => 'required|array',
        'checkout.lineItems.edges'     => 'required|array',
        'preference_params'            => 'required|array',
        'cart'                         => 'required|array',
        'cart.items'                   => 'required|array',
        'cart.token'                   => 'required|string',
    ];

    protected static $createNewShopifyAccountRules = [
       'merchant_id'       => 'required|string|size:14',
       'shop_id'           => 'required|string|max:70',
       'client_id'         => 'required|string|max:150',
       'client_secret'     => 'required|string|max:150',
       'installation_link' => 'required|string|max:100',
    ];


    protected static $updateShopifyMetaFieldsRules = [
        'namespace'  => 'required|string|in:magic_checkout|max:150',
        'metafields' => 'required|array',
    ];

    protected static $insertShopifySnippetRules = [
        'theme_id'    => 'required|string',
        'asset'       => 'required|array',
        'asset.key'   => 'required|string',
        'asset.value' => 'required|string',
    ];

    protected static $renderMagicSnippetRules = [
        'theme_id' => 'required|string',
    ];

}
