<?php

namespace RZP\Gateway\Wallet\Freecharge;

use RZP\Error;
use RZP\Error\ErrorCode;

class ResponseCodeMap
{
    public static $codes = array(
        'E001'  => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_ID,
        'E018'  => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        'E023'  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        'E024'  => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        'E105'  => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        'E603'  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        'E604'  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        'E617'  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        'E618'  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        'E619'  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        'E620'  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        'E621'  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        'E622'  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        'E623'  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        'E624'  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        'E625'  => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE,
        'E626'  => ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED,
        'E627'  => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_REFUND_INITIATED,
        'E628'  => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        'E629'  => ErrorCodE::BAD_REQUEST_PAYMENT_REFUND_AMOUNT_GREATER_THAN_CAPTURED,
        'E701'  => ErrorCode::BAD_REQUEST_PAYMENT_OTP_EXPIRED,
        'E702'  => ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
    );

    public static function getResponseMessage($code)
    {
        $codes = self::$codes;

        return $codes[$code];
    }

    public static function getApiErrorCode($code)
    {
        $class = 'RZP\Error\ErrorCode::';

        if ((empty($code) === true) or
              (isset(self::$codes[$code]) === false))
        {
            return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
        }

        $apiCode = self::$codes[$code];

        if (defined($class . $apiCode))
        {
            return $apiCode;
        }

        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }
}
