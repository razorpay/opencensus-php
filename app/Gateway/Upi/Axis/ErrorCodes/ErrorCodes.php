<?php

namespace RZP\Gateway\Upi\Axis\ErrorCodes;

use RZP\Gateway\Base;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Axis\Constants;

class ErrorCodes extends Base\ErrorCodes\Upi\ErrorCodes
{
    public static $codeMap = [
        '500'  => ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED,
        '444'  => ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED,
        '125'  => ErrorCode::GATEWAY_ERROR_TOKEN_VALIDATION_FAILED,
        '303'  => ErrorCode::GATEWAY_ERROR_DUPLICATE_TOKEN,
        '111'  => [
                Constants::EMPTI      => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
                Constants::DUPLICATE  => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
                Constants::TOKEN      => ErrorCode::GATEWAY_ERROR_TOKEN_NOT_FOUND,
            ],
        'F'    => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        'D'    => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        'P'    => ErrorCode::BAD_REQUEST_PAYMENT_PENDING,
        'E'    => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        'R'    => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED,
        'A79'  => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'A78'  => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        '077'  => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        '076'  => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        '222'  => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        'FL'   => ErrorCode::GATEWAY_ERROR_PAYMENT_FAILED,
        'FP'   => ErrorCode::GATEWAY_ERROR_PAYMENT_FAILED,
    ];

    public static function getErrorCode($code, $content)
    {
        if (isset(self::$codeMap[$code]) === true)
        {
            if (is_array(self::$codeMap[$code]) === true)
            {
                $field = null;

                if (isset($content['result']) === true)
                {
                    $field = 'result';
                }
                else if (isset($content['gatewayResponseCode']) === true)
                {
                    $field = 'gatewayResponseMessage';
                }

                if ($field != null)
                {
                    if (str_contains(strtoupper($content[$field]), Constants::EMPTI) === true)
                    {
                        return self::$codeMap[$code][Constants::EMPTI];
                    }

                    if (str_contains(strtoupper($content[$field]), Constants::DUPLICATE) === true)
                    {
                        return self::$codeMap[$code][Constants::DUPLICATE];
                    }

                    if (str_contains(strtoupper($content[$field]), Constants::TOKEN) === true)
                    {
                        return self::$codeMap[$code][Constants::TOKEN];
                    }
                }

                return ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR;
            }

            return self::$codeMap[$code];
        }

        if (isset(self::$errorCodeMap[$code]) === true)
        {
            return self::$errorCodeMap[$code];
        }

        return ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR;
    }
}
