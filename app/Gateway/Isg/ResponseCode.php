<?php

namespace RZP\Gateway\Isg;

use RZP\Error\ErrorCode;

class ResponseCode
{
    protected static $map = [
        'E001' => 'Amount mismatch in Verify response and callback response',
        'E002' => 'Consumer Pan mismatch in Verify response and callback response',
        'E003' => 'Merchant pan mismatch in Verify response and callback response',
        'E004' => 'Status Code mismatch in Verify response and callback response',
        'E005' => 'No records present for given transaction in Isg Gateway',
        'E006' => 'Transaction is declined by Isg Gateway',
        'E007' => 'Input string cannot be decrypted',
    ];

    protected static $responseCodeToErrorCodeMap = [
        'E001' => ErrorCode::GATEWAY_ERROR_AMOUNT_TAMPERED,
        'E002' => ErrorCode::GATEWAY_ERROR_CONSUMER_PAN_TAMPERED,
        'E003' => ErrorCode::GATEWAY_ERROR_MERCHANT_PAN_TAMPERED,
        'E004' => ErrorCode::GATEWAY_ERROR_STATUS_CODE_MISMATCH,
        'E005' => ErrorCode::GATEWAY_ERROR_TRANSACTION_NOT_PRESENT,
        'E006' => ErrorCode::GATEWAY_ERROR_TRANSACTION_DECLINED,
        'E007' => ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED,
    ];

    public static function getErrorCode(string $code)
    {
        if (isset(self::$map[$code]) === true)
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
