<?php

namespace RZP\Models\Emi;

use RZP\Exception;
use RZP\Models\Emi\AffordabilityProvider;

class PaylaterProvider
{
    const DEFAULT_PAYLATER_PROVIDERS = 0;

    const GETSIMPL = 'getsimpl';
    const LAZYPAY = 'lazypay';
    const ICIC = 'icic';
    const HDFC = 'hdfc';
    const PAYLATER = 'paylater';


    protected static $providers = [
        self::GETSIMPL,
        self::LAZYPAY,
        self::ICIC,
        self::HDFC,
    ];

    public static function checkProviderValidity($provider)
    {
        if (in_array($provider, self::$providers, true) === false)
        {
            throw new Exception\InvalidArgumentException('Invalid paylater provider given');
        }
    }

    public static function getAllPaylaterProviders()
    {
        return self::$providers;
    }

    public static function getEnabledProviders($all_addon_methods, $addon_methods): array
    {
        return AffordabilityProvider::getEnabledProviders($all_addon_methods, $addon_methods, self::PAYLATER);
    }
}
