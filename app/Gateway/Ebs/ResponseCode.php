<?php

namespace RZP\Gateway\Ebs;

use RZP\Error\ErrorCode;

class ResponseCode
{
    public static $reasonCodes = array(
        0   => 'Successful transaction',
        1   => 'Invalid Action',
        2   => 'Invalid Account ID/Secret Key',
        3   => 'Invalid Refrence No',
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
        14  => 'This payment is cancelled already',
        15  => 'This payment can not be cancelled',
        16  => 'Payment has been flagged',
        17  => 'Capture request initiated already',
        18  => 'This amount can not be captured',
        19  => 'Cancel request initiated already',
        20  => 'This amount can not be cancelled',
        21  => 'Less than Rs.21 could not be refunded',
        22  => 'There are no refunds available after 80 days',
        23  => 'Capture request is not processed yet',
        24  => 'This payment is not captured',
        25  => 'Refund request initiated already',
        26  => 'This amount can not be refunded',
        27  => 'Problem in updating payment',
        29  => 'Insufficient balance',
    );

    public static $errorCodeMap = array(
        1   => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ACTION,
        2   => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_SECRET,
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
        14  => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CANCELLED,
        15  => ErrorCode::BAD_REQUEST_PAYMENT_CANNOT_BE_CANCELLED,
        16  => ErrorCode::GATEWAY_ERROR_DENIED_BY_RISK,
        17  => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED,
        18  => ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH,
        19  => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CANCELLED,
        20  => ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_CANNOT_BE_CANCELLED,
        21  => ErrorCode::BAD_REQUEST_PAYMENT_REFUND_LESS_THAN_TWENTY_ONE,
        22  => ErrorCode::BAD_REQUEST_PAYMENT_REFUND_AFTER_EIGHTY_DAYS,
        23  => ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED,
        24  => ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED,
        25  => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_REFUND_INITIATED,
        26  => ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED,
        27  => ErrorCode::BAD_REQUEST_PAYMENT_PROBLEM_IN_UPDATING,
        29  => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
    );

    public static function getMappedCode($code)
    {
        if (isset(self::$errorCodeMap[$code]))
        {
            return self::$errorCodeMap[$code];
        }

        return ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR;
    }
}
