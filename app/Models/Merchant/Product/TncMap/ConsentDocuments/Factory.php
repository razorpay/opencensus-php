<?php

namespace RZP\Models\Merchant\Product\TncMap\ConsentDocuments;

use RZP\Exception;
use RZP\Models\Merchant\Product\Name;

class Factory
{
    public static function getInstance(string $productName)
    {
        switch ($productName)
        {
            case Name::PAYMENT_GATEWAY:
                return new PaymentGatewayConsentDocumentsService();
            case Name::PAYMENT_LINKS:
                return new PaymentLinksConsentDocumentsService();
            case Name::ROUTE:
                return new RouteConsentDocumentsService();
            default:
                throw new Exception\LogicException('invalid product name for fetching consent documents service: '. $productName);
        }
    }
}
