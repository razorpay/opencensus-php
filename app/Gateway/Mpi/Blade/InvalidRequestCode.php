<?php

namespace RZP\Gateway\Mpi\Blade;

use RZP\Error\ErrorCode;

class InvalidRequestCode
{
    const R_50 = '50';
    const R_51 = '51';
    const R_52 = '52';
    const R_53 = '53';
    const R_54 = '54';
    const R_55 = '55';
    const R_56 = '56';
    const R_57 = '57';
    const R_58 = '58';
    const R_98 = '98';
    const R_99 = '99';

    protected static $description = [
        self::R_50 => 'Acquirer not participating in 3-D Secure',
        self::R_51 => 'Merchant not participating in 3-D Secure',
        self::R_52 => 'Password required, but no password was supplied.',
        self::R_53 => 'Supplied password is not valid for combination of Acquirer BIN and Merchant ID.',
        self::R_54 => 'ISO code not valid per ISO tables',
        self::R_55 => 'Transaction data not valid.',
        self::R_56 => 'PAReq sent to wrong ACS',
        self::R_57 => 'Serial Number cannot be located.',
        self::R_58 => 'Issued only by the Directory Server.',
        self::R_98 => 'Transient system failure.',
        self::R_99 => 'Permanent system failure.',
    ];

    protected static $mapping = [];

    public static function map($errorCode)
    {
        if (empty(self::$mapping[$errorCode]) === false)
        {
            return self::$mapping[$errorCode];
        }
    }
}
