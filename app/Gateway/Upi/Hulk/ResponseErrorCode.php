<?php

namespace RZP\Gateway\Upi\Hulk;

use RZP\Error\ErrorCode;

class ResponseErrorCode
{
    protected static $errorCodeMap = [
        'BAD_REQUEST_EXPIRED_VPA'                       => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
        'BAD_REQUEST_FORBIDDEN_TRANSACTION_ON_VPA'      => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
        'BAD_REQUEST_RESTRICTED_VPA'                    => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
        'BAD_REQUEST_INVALID_VPA'                       => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,

        'BAD_REQUEST_P2P_COLLECT_REJECTED_BY_CUSTOMER'  => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED,
        'BAD_REQUEST_P2P_COLLECT_TRANSACTION_EXPIRED'   => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_EXPIRED,

        'BAD_REQUEST_INVALID_AUTH_TRANSACTION_AMOUNT'   => ErrorCode::GATEWAY_ERROR_AMOUNT_TAMPERED,
    ];

    /**
     * First checks the map for incoming error code from hulk,
     * Second, will check error code is already defined in API.
     * Note: Hulk can throw same error code as of API.
     *
     * @param string $errorCode
     * @return mixed|string
     */
    public static function getMappedErrorCode(string $errorCode)
    {
        if (isset(self::$errorCodeMap[$errorCode]) === true)
        {
            return self::$errorCodeMap[$errorCode];
        }
        else if(defined(ErrorCode::class.'::'.$errorCode))
        {
            return constant(ErrorCode::class.'::'.$errorCode);
        }

        return ErrorCode::GATEWAY_ERROR_FATAL_ERROR;
    }
}
