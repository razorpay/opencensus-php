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
        Util\Constants::PAYMENT_CAPTURE => Util\Constants::PAYMENT_CAPTURE,
        Util\Constants::BANK_DETAILS    => Util\Constants::SETTLEMENTS,
        Util\Constants::NOTIFICATIONS   => Util\Constants::NOTIFICATIONS,
        Util\Constants::REQUIREMENTS    => Util\Constants::REQUIREMENTS,
        Util\Constants::PAYMENT_METHODS => Util\Constants::PAYMENT_METHODS
    ];

    const CONFIGS_TO_BE_TRANSFORMED = [
        Util\Constants::ACCOUNT_CONFIG,
        Util\Constants::PAYMENT_CAPTURE,
    ];

    const ACCOUNT_CONFIG_KEYS = [
        Merchant\Entity::LOGO_URL,
        Merchant\Entity::BRAND_COLOR,
        Merchant\Entity::DEFAULT_REFUND_SPEED,
        Util\Constants::FLASH_CHECKOUT
    ];

    const WORKFLOW_NEEDED_CONFIGURATION = [
        'payment_methods'
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

        foreach (self::ACCOUNT_CONFIG_KEYS as $key)
        {
            if (isset($configValue[$key]))
            {
                $response[$key] = $configValue[$key];
            }
        }

        return $response;
    }

    private static function transformPaymentCapture(array $configValue)
    {
        $response = [];

        if (isset($configValue['late_auth']))
        {
            $val = $configValue['late_auth'];

            $response['mode'] = $val['capture'];

            $options = $val['capture_options'];

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
            if($configKey === Constants::REQUIREMENTS)
            {
                continue;
            }

            if ($merchantProduct->getStatus() != 'activated')
            {
                if (in_array($configKey, self::WORKFLOW_NEEDED_CONFIGURATION))
                {
                    $requestedConfiguration[$configKey] = $configValue;
                }
                else
                {
                    $activeConfiguration[$configKey] = $configValue;
                }
            }
            else
            {
                $activeConfiguration[$configKey] = $configValue;
            }
        }

        $publicResponse = [];

        $publicResponse[Util\Constants::REQUESTED_CONFIGURATION] = $requestedConfiguration;

        $publicResponse[Util\Constants::ACTIVE_CONFIGURATION] = $activeConfiguration;

        $publicResponse[Util\Constants::REQUIREMENTS] = $transformedResponse[Util\Constants::REQUIREMENTS] ?? [];

        $publicResponse = array_merge($publicResponse, ProductResponseHelper::getPublicMerchantProduct($merchantProduct));

        return $publicResponse;
    }
}
