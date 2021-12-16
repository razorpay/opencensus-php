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
}
