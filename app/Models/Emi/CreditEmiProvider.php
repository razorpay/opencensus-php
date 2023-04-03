<?php

namespace RZP\Models\Emi;

use RZP\Exception;
use RZP\Models\Emi\AffordabilityProvider;

class CreditEmiProvider
{
    const DEFAULT_CREDIT_EMI_PROVIDERS = 0;

    const HDFC    = 'HDFC';
    const SBIN    = 'SBIN';
    const UTIB    = 'UTIB';
    const ICIC    = 'ICIC';
    const AMEX    = 'AMEX';
    const BARB    = 'BARB';
    const CITI    = 'CITI';
    const HSBC    = 'HSBC';
    const INDB    = 'INDB';
    const KKBK    = 'KKBK';
    const RATN    = 'RATN';
    const SCBL    = 'SCBL';
    const YESB    = 'YESB';
    const ONECARD = 'onecard';
    const BAJAJ   = 'BAJAJ';
    const FDRL    = 'FDRL';
    const CREDIT_EMI = 'credit_emi';
    const CREDIT_EMI_PROVIDERS = 'credit_emi_providers';

    protected static $providers = [
        self::HDFC,
        self::SBIN,
        self::UTIB,
        self::ICIC,
        self::AMEX,
        self::BARB,
        self::CITI,
        self::HSBC,
        self::INDB,
        self::KKBK,
        self::RATN,
        self::SCBL,
        self::YESB,
        self::ONECARD,
        self::BAJAJ,
        self::FDRL
    ];

    public static function checkProviderValidity($provider)
    {
        if (in_array($provider, self::$providers, true) === false)
        {
            throw new Exception\InvalidArgumentException('Invalid credit emi provider given');
        }
    }

    public static function getAllCreditEmiProviders()
    {
        return self::$providers;
    }

    public static function getEnabledProviders($all_addon_methods, $addon_methods): array
    {
        return AffordabilityProvider::getEnabledProviders($all_addon_methods, $addon_methods, self::CREDIT_EMI);
    }

    public static function getConsolidatedEnabledCreditEmiProviders($all_addon_methods, $addon_methods, int $creditEmi): array
    {
        return AffordabilityProvider::getConsolidatedEnabledEmiProviders($all_addon_methods, $addon_methods, self::CREDIT_EMI, $creditEmi);
    }
}
