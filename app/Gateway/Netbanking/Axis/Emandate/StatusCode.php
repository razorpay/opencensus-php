<?php

namespace RZP\Gateway\Netbanking\Axis\Emandate;

use RZP\Error\ErrorCode;

class StatusCode
{
    // Status codes in callback
    const SUCCESS = '000';
    const PENDING = '101';
    const FAILED  = '111';

    // Remarks from debit recon file
    const ERROR_INVALID_CREDS  = 'Invalid User id and Password';

    const EMANDATE_FAILURE = '0';

    const EMANDATE_REGISTRATION_SUCCESS = 'EMANDATE_REGISTRATION_SUCCESS';
    const EMANDATE_REGISTRATION_FAILURE = 'EMANDATE_REGISTRATION_FAILURE';

    protected static $errorCodeMap = [
        self::FAILED  => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::PENDING => ErrorCode::BAD_REQUEST_PAYMENT_PENDING,
    ];

    protected static $errorCodeMapEmandate = [
        self::ERROR_INVALID_CREDS => ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED
    ];

    public static function isSuccess(string $statusCode)
    {
        return ($statusCode === self::SUCCESS);
    }


    /**
     * If the registration fails, the value in mandate number would be 0,
     * else, it would be the mandate number.
     *
     * We're creating custom statuses for emandate success and failure by checking
     * the above mandate number
     */
    public static function getEmandateStatus(string $mandateNumber)
    {
        if (self::isEmandateRegistrationSuccess($mandateNumber))
        {
            return self::EMANDATE_REGISTRATION_SUCCESS;
        }

        return self::EMANDATE_REGISTRATION_FAILURE;
    }

    public static function isEmandateRegistrationSuccess(string $statusCode)
    {
        return ($statusCode !== self::EMANDATE_FAILURE);
    }

    public static function getErrorCodeMap($errorCode)
    {
        return self::$errorCodeMap[$errorCode] ?? ErrorCode::GATEWAY_ERROR_REQUEST_ERROR;
    }

    public static function getEmandateErrorCodeMap($errorCode)
    {
        return self::$errorCodeMapEmandate[$errorCode] ?? ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }
}
