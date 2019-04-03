<?php

namespace RZP\Gateway\CardlessEmi;

use RZP\Error\ErrorCode;

class ErrorCodes
{
    public static $errorCodeMap = [
        'USER_DNE'                         => ErrorCode::BAD_REQUEST_CARDLESS_EMI_USER_DOES_NOT_EXIST,
        'INV_TOKEN'                        => ErrorCode::BAD_REQUEST_CARDLESS_EMI_INVALID_TOKEN,
        'INV_MERCHANT_NAME'                => ErrorCode::BAD_REQUEST_CARDLESS_EMI_INVALID_MERCHANT_NAME,
        'INV_EMI_PLAN_ID'                  => ErrorCode::BAD_REQUEST_CARDLESS_EMI_INVALID_EMI_PLAN_ID,
        'MIN_AMT_REQ'                      => ErrorCode::BAD_REQUEST_CARDLESS_EMI_MINIMUM_AMOUNT_REQUIRED,
        'MAX_AMT_LMT'                      => ErrorCode::BAD_REQUEST_CARDLESS_EMI_MAXIMUM_AMOUNT_LIMIT,
        'PAYMENT_TIMED_OUT'                => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
        'PAYMENT_CANCELLED'                => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED,
        'PAYMENT_FAILED_PARTNER'           => ErrorCode::GATEWAY_ERROR_CARDLESS_EMI_PAYMENT_FAILED_PARTNER,
        'CREDIT_LIMIT_EXHAUSTED'           => ErrorCode::BAD_REQUEST_CARDLESS_EMI_CREDIT_LIMIT_EXHAUSTED,
        'INV_CAPTURE_AMT'                  => ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH,
        'CUST_CREDIT_LIMIT_NOT_ACTIVATED'  => ErrorCode::BAD_REQUEST_CARDLESS_EMI_CREDIT_LIMIT_NOT_ACTIVATED,
        'CUST_CREDIT_LIMIT_NOT_APPROVED'   => ErrorCode::BAD_REQUEST_CARDLESS_EMI_CREDIT_LIMIT_NOT_APPROVED,
        'CUST_CREDIT_LIMIT_EXPIRED'        => ErrorCode::BAD_REQUEST_CARDLESS_EMI_CREDIT_LIMIT_EXPIRED,
    ];

    public static function getInternalErrorCode($errorCode, $defaultErrorCode)
    {
        if (isset(self::$errorCodeMap[$errorCode]) === true)
        {
            return self::$errorCodeMap[$errorCode];
        }

        return $defaultErrorCode;
    }
}
