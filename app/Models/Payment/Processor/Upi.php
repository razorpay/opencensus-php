<?php

namespace RZP\Models\Payment\Processor;

class Upi
{
    const ICICI = 'icici';

    public static $fullName = array(
        self::ICICI         => 'ICICI Bank',
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
