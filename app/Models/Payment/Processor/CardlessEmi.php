<?php

namespace RZP\Models\Payment\Processor;

use RZP\Models\Bank\IFSC;

class CardlessEmi
{
    const EARLYSALARY  = 'earlysalary';
    const ZESTMONEY    = 'zestmoney';
    const FLEXMONEY    = 'flexmoney';

    public static $fullName = [
        self::EARLYSALARY  => 'EarlySalary',
        self::ZESTMONEY    => 'ZestMoney',
        self::FLEXMONEY    => 'FlexMoney',
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
        return (isset(self::$fullName[$provider]) === true);
    }

    public static function getName($provider)
    {
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
}
