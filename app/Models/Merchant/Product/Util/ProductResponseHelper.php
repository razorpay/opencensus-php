<?php

namespace RZP\Models\Merchant\Product\Util;

use RZP\Models\Merchant\Account\Entity;
use RZP\Models\Merchant\Product;

class ProductResponseHelper
{
    public static function handleResponse(Product\Entity $merchantProduct, array $response): array
    {
        $productName = $merchantProduct->getProduct();

        switch ($productName)
        {
            case Product\Name::PAYMENT_GATEWAY:
                $response = PaymentGatewayResponseHandler::handleResponse($merchantProduct, $response);
                break;

        }

        return $response;
    }

    public static function getPublicMerchantProduct(Product\Entity $merchantProduct): array
    {
        $response = $merchantProduct->toArrayPublic();

        $response[Product\Entity::ID] = $merchantProduct->getPublicId();

        $response[Product\Entity::MERCHANT_ID] = Entity::getSignedId($merchantProduct->getMerchantId());

        return $response;
    }
}
