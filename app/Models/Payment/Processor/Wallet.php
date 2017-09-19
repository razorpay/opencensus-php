<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;

class Wallet
{
    const PAYTM       = 'paytm';
    const PAYZAPP     = 'payzapp';
    const MOBIKWIK    = 'mobikwik';
    const PAYUMONEY   = 'payumoney';
    const OLAMONEY    = 'olamoney';
    const AIRTELMONEY = 'airtelmoney';
    const FREECHARGE  = 'freecharge';
    const JIOMONEY    = 'jiomoney';
    const SBIBUDDY    = 'sbibuddy';
    const OPENWALLET  = 'openwallet';
    const MPESA       = 'mpesa';

    public static $fullName = array(
        self::MOBIKWIK      => 'Mobikwik',
        self::OLAMONEY      => 'Olamoney',
        self::PAYTM         => 'Paytm',
        self::PAYUMONEY     => 'Payumoney',
        self::PAYZAPP       => 'Payzapp',
        self::AIRTELMONEY   => 'Airtelmoney',
        self::FREECHARGE    => 'Freecharge',
        self::JIOMONEY      => 'JioMoney',
        self::SBIBUDDY      => 'SBI Buddy',
        self::OPENWALLET    => 'RZP Open Wallet',
        self::MPESA         => 'Vodafone mPesa'
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
                Payment\Entity::WALLET);
        }
    }

    public static function getWalletNetworkNamesMap()
    {
        return self::$fullName;
    }

    public static function getName($wallet)
    {
        return self::$fullName[$wallet];
    }

}
