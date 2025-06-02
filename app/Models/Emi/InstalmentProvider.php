<?php

namespace RZP\Models\Emi;

use RZP\Exception;
use RZP\Models\Emi\AffordabilityProvider;

class InstalmentProvider
{
    const INSTALMENT = 'instalment';
    const VIS = 'vis'; //Visa Instalment Service (Instalment Aggregator)

    protected static $providers = [
        self::VIS,
    ];

    public static function checkProviderValidity($provider)
    {
        if (in_array($provider, self::$providers, true) === false)
        {
            throw new Exception\InvalidArgumentException('Invalid instalment provider given');
        }
    }

    public static function getAllInstalmentProviders()
    {
        return self::$providers;
    }

    public static function getEnabledProviders($all_addon_methods, $addon_methods): array
    {
        return AffordabilityProvider::getEnabledProviders($all_addon_methods, $addon_methods, self::INSTALMENT);
    }
} 