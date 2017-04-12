<?php

namespace RZP\Gateway\Wallet\Mpesa;

class StatusCode
{
    const SUCCESS           = '100';
    const AUTH_FAILURE      = '101';
    const INVALID_PARAMS    = '103';
    const INVALID_MOBILE_NO = '104';
    const PARAMS_MISSING    = '105';
    const FAILURE           = '106';

    protected static $errorCodeMessageMap = [
        self::SUCCESS           => 'Success',
        self::AUTH_FAILURE      => 'Authentication Failed',
        self::INVALID_PARAMS    => 'Invalid mandatory parameters passed',
        self::INVALID_MOBILE_NO => 'Invalid MSISDN',
        self::PARAMS_MISSING    => 'All Mandatory parameters not passed',
        self::FAILURE           => 'Failure',
    ];

    public static function checkIfSuccessStatus($status)
    {
        return ($status === self::SUCCESS);
    }

    public static function getErrorMessage($code)
    {
        return self::$errorCodeMessageMap[$code];
    }
}
