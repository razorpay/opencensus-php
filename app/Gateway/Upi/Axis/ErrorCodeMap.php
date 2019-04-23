<?php

namespace RZP\Gateway\Upi\Axis;

use RZP\Error\ErrorCode;

class ErrorCodeMap
{
    const CODES = [
        Status::TOKEN_CHECKSUM_FAILED   => ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED,
        Status::TOKEN_CHECKSUM_MISMATCH => ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED,
        Status::TOKEN_INCOMPLETE        => ErrorCode::GATEWAY_ERROR_TOKEN_INCOMPLETE,
        Status::TOKEN_VALIDATION_ERROR  => ErrorCode::GATEWAY_ERROR_TOKEN_VALIDATION_FAILED,
        Status::TOKEN_DUPLICATE         => ErrorCode::GATEWAY_ERROR_DUPLICATE_TOKEN,
        Status::COLLECT_DUPLICATE       => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        Status::COLLECT_TOKEN_NOT_FOUND => ErrorCode::GATEWAY_ERROR_TOKEN_NOT_FOUND,
        Status::COLLECT_INVALID_VPA     => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
        Status::VERIFY_FAILED           => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        Status::VERIFY_DEEMED           => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        Status::VERIFY_PENDING          => ErrorCode::BAD_REQUEST_PAYMENT_PENDING,
        Status::VERIFY_EXPIRED          => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        Status::VERIFY_REJECT           => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED,
        Status::CALLBACK_FAILED         => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        Status::CALLBACK_REJECTED       => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED,
        Status::REFUND_ABSENT           => ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT,
        'A79'                           => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'A78'                           => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        '077'                           => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        '076'                           => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        '222'                           => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        'ML01'                          => ErrorCode::GATEWAY_ERROR_MULTIPLE_REFUNDS_FOUND,
    ];

    const CODEMAP = [
        Status::TOKEN_CHECKSUM_FAILED   => 'Internal Server Error - checksum not generated properly',
        Status::TOKEN_CHECKSUM_MISMATCH => 'Checksum does not match',
        Status::TOKEN_INCOMPLETE        => 'Missing or empty parameter',
        Status::TOKEN_VALIDATION_ERROR  => 'Validation error - invalid special characters in txn id',
        Status::TOKEN_DUPLICATE         => 'Duplicate transaction ID',
        Status::COLLECT_DUPLICATE       => 'Duplicate collect request',
        Status::COLLECT_TOKEN_NOT_FOUND => 'Token not found',
        Status::COLLECT_INVALID_VPA     => 'Invalid VPA',
        Status::VERIFY_FAILED           => 'Failed transaction',
        Status::VERIFY_DEEMED           => 'Deemed transaction',
        Status::VERIFY_PENDING          => 'Transaction is pending',
        Status::VERIFY_EXPIRED          => 'Transaction expired',
        Status::VERIFY_REJECT           => 'Transaction rejected',
        Status::CALLBACK_FAILED         => 'Payment failed',
        Status::CALLBACK_REJECTED       => 'Payment rejected',
        Status::REFUND_ABSENT           => 'Refund not found',
        'A79'                           => 'REFUND AMOUNT CANNOT BE ZERO OR NEGATIVE',
        'A78'                           => 'DUPLICATE REFUND ID',
        '077'                           => 'RECORD NOT AVAILABLE',
        '076'                           => 'REFUND LIMIT CROSSED',
        '222'                           => 'ERROR WHILE PROCESSING REFUND REQUEST',
        'ML01'                          => 'Multiple Order Ids found',
    ];



    public static function getApiErrorCode($code)
    {
        if ((empty($code) === true) or
            (isset(self::CODES[$code]) === false))
        {
            return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
        }

        return self::CODES[$code];
    }

    public static function getResponseMessage($code)
    {
        return self::CODEMAP[$code] ?? 'Unknown Gateway Response Code';
    }
}
