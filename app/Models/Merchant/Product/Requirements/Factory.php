<?php

namespace RZP\Models\Merchant\Product\Requirements;

use RZP\Exception;
use RZP\Models\Merchant\Product\Name;

class Factory
{
    public static function getInstance(string $productName)
    {
        switch ($productName)
        {
            case Name::PAYMENT_GATEWAY:
                return new PaymentGatewayRequirementService();
            case Name::PAYMENT_LINKS:
                return new PaymentLinksRequirementService();
            case Name::ROUTE:
                return new RouteRequirementService();
            default:
                throw new Exception\LogicException('invalid product name for fetching requirement service: '. $productName);
        }
    }
}
