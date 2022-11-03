<?php

namespace RZP\Models\Merchant\Product\Util;

use RZP\Models\Merchant\Product\Name;
use RZP\Models\Merchant\Product\Config;

class ProductRequestHandler
{
    public static function handleRequest(string $productName, array $request): array
    {
        switch ($productName)
        {
            case Name::PAYMENT_GATEWAY:
            case Name::PAYMENT_LINKS:
                (new Config\Validator)->validateInput('pg', $request);
                $request = PaymentGatewayRequestHandler::handleRequest($request);
                break;
            case Name::ROUTE:
                (new Config\Validator)->validateInput('route_product', $request);
                $request = PaymentGatewayRequestHandler::handleRequest($request);
                break;
        }

        return $request;
    }
}
