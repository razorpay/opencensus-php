<?php

namespace RZP\Models\Payment\Processor;

class Upi
{
    const ICICI     = 'icici';
    const HDFC      = 'hdfc';
    const SBI       = 'sbi';

    // TODO: shift this to IFSC::getBankName()
    public static $fullName = array(
        self::ICICI         => 'ICICI Bank',
        self::HDFC          => 'HDFC Bank',
        self::SBI           => 'SBI Bank',
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
