<?php

namespace RZP\Models\Payment\Processor;

class Wallet
{
    const PAYTM       = 'paytm';
    const PAYZAPP     = 'payzapp';
    const MOBIKWIK    = 'mobikwik';
    const PAYUMONEY   = 'payumoney';
    const OLAMONEY    = 'olamoney';
    const AIRTELMONEY = 'airtelmoney';
    const FREECHARGE  = 'freecharge';

    public static $fullName = array(
        self::MOBIKWIK      => 'Mobikwik',
        self::OLAMONEY      => 'Olamoney',
        self::PAYTM         => 'Paytm',
        self::PAYUMONEY     => 'Payumoney',
        self::PAYZAPP       => 'Payzapp',
        self::AIRTELMONEY   => 'Airtelmoney',
        self::FREECHARGE    => 'Freecharge',
    );

    public static function exists($wallet)
    {
        return defined(get_class().'::'.strtoupper($wallet));
    }

    public static function validateExists($wallet)
    {
        if (self::exists($wallet) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED,
                Entity::WALLET);
        }
    }

    public static function getWalletNetworkNamesMap()
    {
        return self::$fullName;
    }
}
