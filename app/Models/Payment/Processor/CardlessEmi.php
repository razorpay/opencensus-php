<?php

namespace RZP\Models\Payment\Processor;

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

    public static function exists($provider)
    {
        return (isset(self::$fullName[$provider]) === true);
    }

    public static function getName($provider)
    {
        return self::$fullName[$provider];
    }

    public static function getCardlessEmiDirectAquirers()
    {
        return array_keys(self::$fullName);
    }
}
