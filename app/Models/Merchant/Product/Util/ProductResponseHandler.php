<?php

namespace RZP\Models\Merchant\Product\Util;

use RZP\Models\Merchant\Product;
use RZP\Models\Merchant\Account\Entity;
use RZP\Models\Merchant\Account\Constants as AccountConstants;

class ProductResponseHandler
{
    public static function handleResponse(Product\Entity $merchantProduct, array $response): array
    {
        $productName = $merchantProduct->getProduct();

        switch ($productName)
        {
            case Product\Name::PAYMENT_GATEWAY:
            case Product\Name::PAYMENT_LINKS:
            case Product\Name::ROUTE:
                $response = self::getPaymentGatewayResponse($merchantProduct, $response);

                break;
            case Product\Name::LINE_OF_CREDIT:
                $response = self::getPaymentGatewayResponse($merchantProduct, $response);
                $response = self::overrideResponseForLOCProduct($response);
                break;
        }

        return $response;
    }

    public static function getPublicMerchantProduct(Product\Entity $merchantProduct): array
    {
        $response = $merchantProduct->toArrayPublic();

        unset($response[Product\Entity::MERCHANT_ID]);

        $response[Product\Entity::ID] = $merchantProduct->getPublicId();

        $response[Constants::ACCOUNT_ID] = Entity::getSignedId($merchantProduct->getMerchantId());

        $response[Constants::REQUESTED_AT] = $merchantProduct->getCreatedAt();

        return $response;
    }

    /**
     * @param Product\Entity $merchantProduct
     * @param array $response
     * @return array
     */
    private static function getPaymentGatewayResponse(Product\Entity $merchantProduct, array $response): array
    {
        $activeConfig  = [];
        $pendingConfig = [];
        if (isset($response[Constants::PAYMENT_METHODS]) === true)
        {
            [$activeConfig, $pendingConfig] = PaymentMethodsResponseHandler::handleResponse($response[Constants::PAYMENT_METHODS]);
            unset($response[Constants::PAYMENT_METHODS]);
        }

        if (isset($response[Constants::PAYMENT_METHODS_UPDATE]) === true)
        {
            $pendingConfig[Constants::PAYMENT_METHODS] = $response[Constants::PAYMENT_METHODS_UPDATE];
            unset($response[Constants::PAYMENT_METHODS_UPDATE]);
        }

        $response = PaymentGatewayResponseHandler::handleResponse($merchantProduct, $response);

        $response[Constants::ACTIVE_CONFIGURATION]    = array_merge($response[Constants::ACTIVE_CONFIGURATION], $activeConfig);
        $response[Constants::REQUESTED_CONFIGURATION] = array_merge($response[Constants::REQUESTED_CONFIGURATION], $pendingConfig);

        // map activation status to public account status
        if (isset($response[Product\Entity::ACTIVATION_STATUS]) === true )
        {
            $merchantProduct->load('merchant');
            $merchant = $merchantProduct->merchant;
            $activationStatus = (empty($merchant)=== false && $merchant->isSuspended()) ? AccountConstants::SUSPENDED: $response[Entity::ACTIVATION_STATUS];
            $response[Entity::ACTIVATION_STATUS] = AccountConstants::ACTIVATION_STATUS_ACCOUNT_STATUS_MAPPING[$activationStatus];
        }
        return $response;
    }

    /**
     * While onboarding a merchant to LOC, the 'activation_status' field (in Product config API response) isn't applicable.
     * Instead, the LOC application state matters. Hence, for now we are unsetting 'activation_status' field
     * from Product config API response.
     *
     * @param array $response
     *
     * @return array
     */
    private static function overrideResponseForLOCProduct(array $response): array
    {
        if (isset($response[Product\Entity::ACTIVATION_STATUS]) === true)
        {
            unset($response[Product\Entity::ACTIVATION_STATUS]);
        }

        return $response;
    }

}
