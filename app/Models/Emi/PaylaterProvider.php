<?php

namespace RZP\Models\Emi;

use RZP\Exception;
use RZP\Models\Emi\AffordabilityProvider;

class PaylaterProvider
{
    const DEFAULT_PAYLATER_PROVIDERS = 0;

    const GETSIMPL = 'getsimpl';
    const GETSIMPLOPTIMIZER = 'getsimpl_optimizer';
    const LAZYPAY = 'lazypay';
    const ICIC = 'icic';
    const HDFC = 'hdfc';
    const AMAZONPAY = 'amazonpay';
    const RZPXPOSTPAID = 'rzpx_postpaid';
    const PAYLATER = 'paylater';
    const ATOME = 'atome';

    //PPRO APM Paylater
    const KLARNA = 'klarna';
    const ZIP = 'zip';


    protected static $providers = [
        self::GETSIMPL,
        self::LAZYPAY,
        self::ICIC,
        self::HDFC,
        self::AMAZONPAY,
        self::RZPXPOSTPAID,
        self::ATOME,
        self::GETSIMPLOPTIMIZER,
        self::KLARNA,
        self::ZIP
    ];

    public static $disabledInstruments = [
        self::GETSIMPL,
    ];

    public static $experimentCheckRequiredPaylaterProviders = [
        self::LAZYPAY => 'app.lazypay_whitelisted_merchants_experiment_id',
        self::ICIC => 'app.icic_whitelisted_merchants_experiment_id',
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
