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
            
                $response = self::getPaymentGatewayResponse($merchantProduct, $response);

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

    /**
     * @param Product\Entity $merchantProduct
     * @param array $response
     * @return array
     */
    private static function getPaymentGatewayResponse(Product\Entity $merchantProduct, array $response): array
    {
        $activeConfig = [];
        $pendingConfig = [];
        if(isset($response[Constants::PAYMENT_METHODS]))
        {
            [$activeConfig, $pendingConfig] = PaymentMethodsResponseHandler::handleResponse($response[Constants::PAYMENT_METHODS]);
            unset($response[Constants::PAYMENT_METHODS]);
        }
        $response = PaymentGatewayResponseHandler::handleResponse($merchantProduct, $response);

        $response[Constants::ACTIVE_CONFIGURATION] =  array_merge($response[Constants::ACTIVE_CONFIGURATION], $activeConfig);
        $response[Constants::REQUESTED_CONFIGURATION] =  array_merge($response[Constants::REQUESTED_CONFIGURATION], $pendingConfig);

        return $response;
    }

}
