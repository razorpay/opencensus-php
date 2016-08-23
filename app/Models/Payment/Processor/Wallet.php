<?php

namespace RZP\Models\Payment\Processor;

class Wallet
{
    const PAYTM     = 'paytm';
    const PAYZAPP   = 'payzapp';
    const MOBIKWIK  = 'mobikwik';
    const PAYUMONEY = 'payumoney';
    const OLAMONEY  = 'olamoney';

    public static $fullName = array(
        self::MOBIKWIK      => 'Mobikwik',
        self::OLAMONEY      => 'Olamoney',
        self::PAYTM         => 'Paytm',
        self::PAYUMONEY     => 'Payumoney',
        self::PAYZAPP       => 'Payzapp',
    );

    public static function exists($wallet)
    {
        return defined(get_class().'::'.strtoupper($wallet));
    }

    public static function getWalletNetworkNamesMap()
    {
    	return self::$fullName;
    }
}
