<?php

namespace RZP\Gateway\Upi\Sbi;

use RZP\Error\ErrorCode;

class Status
{
    const SUCCESS          = 'S';
    const PENDING          = 'P';
    const FAILED           = 'F';
    const REJECTED         = 'R';
    const EXPIRED          = 'X';
    const VALIDATION_ERROR = 'V';

    const STATUS_CODE_TO_MESSAGE_MAP = [
        self::SUCCESS          => 'Payment Successful',
        self::PENDING          => 'Transaction Pending waiting for response',
        self::FAILED           => 'Payment failed',
        self::REJECTED         => 'Collect request rejected by customer',
        self::EXPIRED          => 'Collect request expired',
        self::VALIDATION_ERROR => 'Request Validation Error',
    ];

    const STATUS_CODE_TO_ERROR_CODE_MAP = [
        self::FAILED           => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::REJECTED         => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED,
        self::EXPIRED          => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_EXPIRED,
        self::PENDING          => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_PENDING,
        self::VALIDATION_ERROR => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
    ];

    public static function isStatusSuccess(string $status)
    {
        return ($status === self::SUCCESS);
    }

    public static function getMessage(string $status)
    {
        return self::STATUS_CODE_TO_MESSAGE_MAP[$status] ?? self::STATUS_CODE_TO_MESSAGE_MAP[self::FAILED];
    }

    public static function getErrorCode(string $status)
    {
        return self::STATUS_CODE_TO_ERROR_CODE_MAP[$status] ?? ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }
}
