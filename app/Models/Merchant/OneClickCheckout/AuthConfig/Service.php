<?php

namespace RZP\Models\Merchant\OneClickCheckout\AuthConfig;

use RZP\Models\Base;
use RZP\Models\Merchant;

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
}
