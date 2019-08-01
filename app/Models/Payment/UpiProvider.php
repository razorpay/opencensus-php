<?php

namespace RZP\Models\Payment;

use RZP\Models\Feature;


class UpiProvider
{
    const GOOGLE_PAY = 'google_pay';

    public static $omnichannelProviders = [
        self::GOOGLE_PAY
    ];

    public static $upiProvidersToGatewayMap = [
        self::GOOGLE_PAY => Gateway::GOOGLE_PAY
    ];

    public static function isValidOmnichannelProvider(string $provider): bool
    {
        if (in_array($provider, self::$omnichannelProviders) === true)
        {
            return true;
        }

        return false;
    }

    /**
     * Stores the mapping of the upi_provider to their feature
     */
    public static $upiProviderToFeatureMap = [
        self::GOOGLE_PAY => Feature\Constants::GOOGLE_PAY_OMNICHANNEL,
    ];
}
