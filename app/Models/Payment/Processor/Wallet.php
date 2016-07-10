<?php

namespace RZP\Models\Payment\Processor;

class Wallet
{
    const PAYTM     = 'paytm';
    const PAYZAPP   = 'payzapp';
    const MOBIKWIK  = 'mobikwik';
    const PAYUMONEY = 'payumoney';

    public static $fullName = array(
        self::PAYTM         => 'Paytm',
        self::PAYZAPP       => 'Payzapp',
        self::MOBIKWIK      => 'Mobikwik',
        self::PAYUMONEY     => 'Payumoney');

    public static function exists($wallet)
    {
        return defined(get_class().'::'.strtoupper($wallet));
    }

    public static function getWalletNetworkNamesMap()
    {
    	return self::$fullName;
    }
}
