<?php

namespace RZP\Gateway\Ebs;

use RZP\Error\ErrorCode;

class ResponseCode
{
    public static $codes = array(
        0   => 'Successful transaction',
        1   => 'Invalid Action',
        2   => 'Payment processing failed due to error at bank or wallet gateway',
        3   => 'Invalid Reference No',
        4   => 'Invalid TransactionID/PaymentID',
        5   => 'Problem in retrieving transaction',
        6   => 'Currency is empty',
        7   => 'This currency is not supported now',
        8   => 'Amount should be a numeric value',
        9   => 'Amount must be greater than zero',
        10  => 'Invalid PaymentID',
        11  => 'This payment is not authorized',
        12  => 'This payment is failed',
        13  => 'This payment is captured already',
        16  => 'Payment has been flagged',
        23  => 'Capture request is not processed yet',
        24  => 'This payment is not captured',
        25  => 'Refund request initiated already',
        27  => 'Problem in updating payment',
        29  => 'Insufficient balance',
    );

    public static $codeMap = array(
        1   => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ACTION,
        2   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        3   => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_REFERENCE_NO,
        4   => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID,
        5   => ErrorCode::GATEWAY_ERROR_PAYMENT_CANNOT_BE_RETRIEVED,
        6   => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY,
        7   => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY,
        8   => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        9   => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        10  => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ID,
        11  => ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_ONLY_AUTHORIZED,
        12  => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        13  => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED,
        16  => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK,
        23  => ErrorCode::BAD_REQUEST_PAYMENT_STATUS_CAPTURE_NOT_PROCESSED,
        24  => ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED,
        25  => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_REFUND_INITIATED,
        27  => ErrorCode::BAD_REQUEST_PAYMENT_PROBLEM_IN_UPDATING,
        29  => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
    );

    public static function getMappedCode($code)
    {
        if (isset(self::$codeMap[$code]))
        {
            return self::$codeMap[$code];
        }

        return ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR;
    }
}
