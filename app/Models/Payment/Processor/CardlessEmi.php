<?php

namespace RZP\Models\Payment\Processor;

use RZP\Models\Bank\IFSC;

class CardlessEmi
{
    const EARLYSALARY  = 'earlysalary';
    const ZESTMONEY    = 'zestmoney';
    const FLEXMONEY    = 'flexmoney';

    const HDFC = 'hdfc';
    const KKBK = 'kkbk';
    const FDRL = 'fdrl';
    const IDFB = 'idfb';

    public static $fullName = [
        self::EARLYSALARY  => 'EarlySalary',
        self::ZESTMONEY    => 'ZestMoney',
        self::FLEXMONEY    => 'FlexMoney',
    ];

    public static $fullNameForSupportedBanks = [
        self::HDFC    => 'hdfc',
        self::KKBK    => 'kkbk',
        self::FDRL    => 'fdrl',
        self::IDFB    => 'idfb',
    ];

    public static $supportedBanks = [
        self::FLEXMONEY => [
            IFSC::HDFC,
            IFSC::KKBK,
            IFSC::FDRL,
            IFSC::IDFB,
        ]
    ];

    public static $defaultDisabledBanks = [
        self::FLEXMONEY => [
            IFSC::FDRL,
            IFSC::IDFB,
        ]
    ];

    public static function exists($provider)
    {
        if (self::getProviderForBank($provider) != null)
        {
            return true;
        }
        return (isset(self::$fullName[$provider]) === true);
    }

    public static function getName($provider)
    {
        if (self::getProviderForBank($provider) != null)
        {
            return self::$fullNameForSupportedBanks[$provider];
        }
        return self::$fullName[$provider];
    }

    public static function isMultilenderProvider($provider)
    {
        return array_key_exists($provider, self::$supportedBanks);
    }

    public static function getSupportedBanksForMultilenderProvider($provider)
    {
        return self::$supportedBanks[$provider];
    }

    public static function getDefaultDisabledBanksForMultilenderProvider($provider)
    {
        return self::$defaultDisabledBanks[$provider];
    }

    public static function getCardlessEmiDirectAquirers()
    {
        return array_keys(self::$fullName);
    }

    public static function getProviderForBank($bank)
    {
        foreach (self::$supportedBanks as $provider => $supportedBanks)
        {
            if (in_array(strtoupper($bank), $supportedBanks) === true)
            {
                return $provider;
            }
        }
        return null;
    }
}
