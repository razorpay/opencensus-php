<?php

namespace RZP\Models\Payment\Processor;

class Upi
{
    const ICICI     = 'icici';
    const HDFC      = 'hdfc';
    const SBIN      = 'sbin';

    public static $fullName = array(
        self::ICICI         => 'ICICI Bank',
        self::HDFC          => 'HDFC Bank',
        self::SBIN          => 'SBI Bank',
    );

    public static function exists($bank)
    {
        return defined(__CLASS__ . '::' . strtoupper($bank));
    }

    public static function getFullBankNamesMap()
    {
        return self::$fullName;
    }
}
