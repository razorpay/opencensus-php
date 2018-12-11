<?php

namespace RZP\Models\Payment\Processor;

class CardlessEmi
{
    const EARLYSALARY  = 'earlysalary';
    const ZESTMONEY    = 'zestmoney';

    public static $fullName = [
        self::EARLYSALARY  => 'EarlySalary',
        self::ZESTMONEY    => 'ZestMoney',
    ];

    public static function exists($provider)
    {
        return (isset(self::$fullName[$provider]) === true);
    }
}
