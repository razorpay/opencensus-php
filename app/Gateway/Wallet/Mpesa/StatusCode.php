<?php

namespace RZP\Gateway\Wallet\Mpesa;

use RZP\Error\ErrorCode;

class StatusCode
{
    const SUCCESS            = '100';
    const AUTH_FAILURE       = '101';
    const INVALID_PARAMS     = '103';
    const INVALID_MOBILE_NO  = '104';
    const PARAMS_MISSING     = '105';
    const FAILURE            = '106';
    const TIMEOUT            = '107';

    const RANDOM_MPESA_ERROR = 'Vodafone Mpesa Failure';

    /**
     * Maps status codes to messages
     * @var array
     */
    protected static $statusCodeToMessageMap = [
        self::SUCCESS           => 'Success',
        self::AUTH_FAILURE      => 'Authentication Failed',
        self::INVALID_PARAMS    => 'Invalid mandatory parameters passed',
        self::INVALID_MOBILE_NO => 'Invalid MSISDN',
        self::PARAMS_MISSING    => 'All Mandatory parameters not passed',
        self::FAILURE           => 'Failure',
        self::TIMEOUT           => 'CBS Timeout',
    ];

    /**
     * Maps status codes to internal error codes
     * @var array
     */
    protected static $statusCodeToErrorCodeMap = [
        self::AUTH_FAILURE      => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_AUTHENTICATION_FAILED,
        self::INVALID_PARAMS    => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::INVALID_MOBILE_NO => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_MOBILE,
        self::PARAMS_MISSING    => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::FAILURE           => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::TIMEOUT           => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
    ];

    public static function getErrorCode(string $code)
    {
        if (isset(self::$statusCodeToErrorCodeMap[$code]) === true)
        {
            return self::$statusCodeToErrorCodeMap[$code];
        }

        return ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR;
    }

    public static function getErrorMessage(string $code)
    {
        if (isset(self::$statusCodeToMessageMap[$code]) === true)
        {
            return self::$statusCodeToMessageMap[$code];
        }

        return self::RANDOM_MPESA_ERROR;
    }

    public static function isStatusSuccess(string $status)
    {
        return ($status === self::SUCCESS);
    }
}
