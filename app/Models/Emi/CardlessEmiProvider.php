<?php

namespace RZP\Models\Emi;

use RZP\Exception;
use RZP\Models\Emi\AffordabilityProvider;

class CardlessEmiProvider
{
    const DEFAULT_CARDLESS_EMI_PROVIDERS = 0;


    const WALNUT369 = 'walnut369';
    const ZESTMONEY = 'zestmoney';
    const EARLYSALARY = 'earlysalary';
    const HDFC = 'hdfc';
    const ICIC = 'icic';
    const BARB = 'barb';
    const KKBK = 'kkbk';
    const FDRL = 'fdrl';
    const IDFB = 'idfb';
    const HCIN = 'hcin';
    const CARDLESS_EMI = 'cardless_emi';
    const KRBE = 'krbe';
    const CSHE = 'cshe';
    const TVSC = 'tvsc';

    protected static $providers = [
        self::ZESTMONEY,
        self::EARLYSALARY,
        self::WALNUT369,
        self::HDFC,
        self::ICIC,
        self::BARB,
        self::KKBK,
        self::FDRL,
        self::IDFB,
        self::HCIN,
        self::KRBE,
        self::CSHE,
        self::TVSC,
    ];

    public static $disabledInstruments = [
        self::HCIN,
        self::FDRL,
        self::ZESTMONEY,
        self::BARB,
    ];

    public static function checkProviderValidity($provider)
    {
        if (in_array($provider, self::$providers, true) === false)
        {
            throw new Exception\InvalidArgumentException('Invalid cardless_emi provider given');
        }
    }

    public static function getAllCardlessEmiProviders()
    {
        return self::$providers;
    }

    public static function getEnabledProviders($all_addon_methods, $addon_methods): array
    {
        return AffordabilityProvider::getEnabledProviders($all_addon_methods, $addon_methods, self::CARDLESS_EMI);
    }
}
