<?php

namespace RZP\Models\Merchant\Product\Util;

use RZP\Models\Merchant;
use RZP\Models\Merchant\Product;
use RZP\Models\Merchant\Product\Util;

class PaymentGatewayResponseHandler
{
    const RESPONSE_CONFIG_KEYS = [
        Merchant\Entity::ID             => Merchant\Entity::ID,
        Util\Constants::ACCOUNT_CONFIG  => Util\Constants::CHECKOUT,
        Util\Constants::REFUND          => Util\Constants::REFUND,
        Util\Constants::PAYMENT_CAPTURE => Util\Constants::PAYMENT_CAPTURE,
        Util\Constants::BANK_DETAILS    => Util\Constants::SETTLEMENTS,
        Util\Constants::NOTIFICATIONS   => Util\Constants::NOTIFICATIONS,
        Util\Constants::REQUIREMENTS    => Util\Constants::REQUIREMENTS,
        Util\Constants::PAYMENT_METHODS => Util\Constants::PAYMENT_METHODS,
        Util\Constants::TNC             => Util\Constants::TNC,
        Util\Constants::OTP             => Util\Constants::OTP
    ];

    const CONFIGS_TO_BE_TRANSFORMED = [
        Util\Constants::ACCOUNT_CONFIG,
        Util\Constants::PAYMENT_CAPTURE,
    ];

    const ACCOUNT_CONFIG_KEY_MAPPING = [
        Merchant\Entity::LOGO_URL             => Constants::LOGO,
        Merchant\Entity::BRAND_COLOR          => Constants::THEME_COLOR,
        Util\Constants::FLASH_CHECKOUT        => Util\Constants::FLASH_CHECKOUT
    ];

    public static function handleResponse(Product\Entity $merchantProduct, array $response)
    {
        $transformedResponse = [];

        foreach ($response as $configKey => $configValue)
        {
            if (in_array($configKey, self::CONFIGS_TO_BE_TRANSFORMED))
            {
                $function = 'transform' . studly_case($configKey);

                $transformedResponse[self::RESPONSE_CONFIG_KEYS[$configKey]] = self::$function($configValue);
            }
            else
            {
                $transformedResponse[self::RESPONSE_CONFIG_KEYS[$configKey]] = $configValue;
            }
        }

        return self::getPublicResponse($transformedResponse, $merchantProduct);
    }

    private static function transformAccountConfig(array $configValue)
    {
        $response = [];

        foreach (self::ACCOUNT_CONFIG_KEY_MAPPING as $key => $publicKey)
        {
            if (isset($configValue[$key]) === true)
            {
                $response[$publicKey] = $configValue[$key];
            }
        }

        return $response;
    }

    private static function transformPaymentCapture(array $configValue)
    {
        $response = [];

        if (isset($configValue[Util\Constants::LATE_AUTH]) === true)
        {
            $val = $configValue[Util\Constants::LATE_AUTH];

            $response[Util\Constants::MODE] = $val[Constants::CAPTURE];

            $options = $val[Util\Constants::CAPTURE_OPTIONS];

            $response = array_merge($response, $options);
        }

        return $response;
    }

    private static function getPublicResponse(array $transformedResponse, Product\Entity $merchantProduct): array
    {
        $activeConfiguration = [];

        $requestedConfiguration = [];

        foreach ($transformedResponse as $configKey => $configValue)
        {
            if($configKey === Constants::REQUIREMENTS || $configKey === Constants::TNC)
            {
                continue;
            }

            $activeConfiguration[$configKey] = $configValue;
        }

        $publicResponse = [];

        $publicResponse[Util\Constants::REQUESTED_CONFIGURATION] = $requestedConfiguration;

        $publicResponse[Util\Constants::ACTIVE_CONFIGURATION] = $activeConfiguration;

        $publicResponse[Util\Constants::REQUIREMENTS] = $transformedResponse[Util\Constants::REQUIREMENTS] ?? [];

        $publicResponse[Util\Constants::TNC] = $transformedResponse[Util\Constants::TNC] ?? [];

        $publicResponse = array_merge($publicResponse, ProductResponseHandler::getPublicMerchantProduct($merchantProduct));

        return $publicResponse;
    }
}
