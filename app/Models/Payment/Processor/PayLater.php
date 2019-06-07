<?php

namespace RZP\Models\Payment\Processor;

class PayLater
{
    const EPAYLATER    = 'epaylater';

    public static $fullName = [
        self::EPAYLATER    => 'ePayLater',
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
