<?php

namespace RZP\Models\Merchant\Credits\Balance;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function getCreditsBalancesOfMerchantForProduct(string $product)
    {
        (new Validator)->validateProduct($product);

        $response = (new Core)->getCreditsBalancesOfMerchantForProduct($this->merchant, $product);

        return $response;
    }
}
