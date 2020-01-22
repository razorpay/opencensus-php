<?php

namespace RZP\Models\Payment\Processor;

class PayLater
{
    const EPAYLATER    = 'epaylater';
    const GETSIMPL     = 'getsimpl';
    const ICICI        = 'icic';

    public static $fullName = [
        self::EPAYLATER    => 'ePayLater',
        self::GETSIMPL     => 'getsimpl',
        self::ICICI        => 'icic',
    ];

    public static function exists($provider)
    {
        return (isset(self::$fullName[$provider]) === true);
    }

    public static function getName($provider)
    {
        return self::$fullName[$provider];
    }
}
