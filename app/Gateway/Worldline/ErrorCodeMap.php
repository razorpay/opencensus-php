<?php

namespace RZP\Gateway\Worldline;

use RZP\Error\ErrorCode;

class ErrorCodeMap
{
    protected static $map = [
        'E001' => 'Amount mismatch in Verify response and callback response',
        'E002' => 'Consumer Pan mismatch in Verify response and callback response',
        'E003' => 'Merchant pan mismatch in Verify response and callback response',
        'E005' => 'Customer VPA mismatch in Verify response and callback response',
        'E007' => 'Input string cannot be decrypted',
        'E006' => 'PENDING',
        'E008' => 'Server Internal Error'
    ];

    protected static $responseCodeToErrorCodeMap = [
        'E001' => ErrorCode::GATEWAY_ERROR_AMOUNT_TAMPERED,
        'E002' => ErrorCode::GATEWAY_ERROR_CONSUMER_PAN_TAMPERED,
        'E003' => ErrorCode::GATEWAY_ERROR_MERCHANT_PAN_TAMPERED,
        'E005' => ErrorCode::BAD_REQUEST_UNMAPPED_VPA,
        'E007' => ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED,
        'E006' => ErrorCode::BAD_REQUEST_PAYMENT_PENDING,
        'E008' => ErrorCode::GATEWAY_ERROR_INTERNAL_SERVER_ERROR,
    ];

    public static function getErrorCode(string $code)
    {
        if (isset(self::$responseCodeToErrorCodeMap[$code]) === true)
        {
            return self::$responseCodeToErrorCodeMap[$code];
        }

        return null;
    }

    public static function getResponseCodeMessage(string $code)
    {
        if (isset(self::$map[$code]) === true)
        {
            return self::$map[$code];
        }

        return null;
    }
}
