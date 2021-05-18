<?php

namespace RZP\Models\Merchant\Product\Util;

use RZP\Models\Merchant\Product\Name;
use RZP\Models\Merchant\Product\Config;

class ProductRequestHelper
{
    public static function handleRequest(string $productName, array $request): array
    {
        switch ($productName)
        {
            case Name::PAYMENT_GATEWAY:
                (new Config\Validator)->validateInput('pg', $request);
                $request = PaymentGatewayRequestHelper::handleRequest($request);
                break;

        }

        return $request;
    }
}
