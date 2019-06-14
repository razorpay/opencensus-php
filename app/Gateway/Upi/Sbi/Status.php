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
    const CBS_DOWN         = 'T';

    /**
     * Below are the statuses for validateVpa API
     */
    const AVAILABLE_VPA   = 'VE';
    const UNAVAILABLE_VPA = 'VN';

    const STATUS_CODE_TO_MESSAGE_MAP = [
        self::SUCCESS          => 'Payment Successful',
        self::PENDING          => 'Transaction Pending waiting for response',
        self::FAILED           => 'Payment failed',
        self::REJECTED         => 'Collect request rejected by customer',
        self::EXPIRED          => 'Collect request expired',
        self::VALIDATION_ERROR => 'Request Validation Error',
        self::AVAILABLE_VPA    => 'Vpa Available',
        self::UNAVAILABLE_VPA  => 'Vpa Unavailable',
        self::CBS_DOWN         => 'CBS transaction processing timed out',
    ];

    const STATUS_CODE_TO_ERROR_CODE_MAP = [
        self::FAILED           => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::REJECTED         => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED,
        self::EXPIRED          => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_EXPIRED,
        self::PENDING          => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_PENDING,
        self::VALIDATION_ERROR => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        self::UNAVAILABLE_VPA  => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,

        // When T is returned in any flow other than callback, we consider it to be a time out
        self::CBS_DOWN         => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT
    ];

    public static function isStatusSuccess(string $status, string $action)
    {
        if ($action === Action::VALIDATE_VPA)
        {
            return ($status === self::AVAILABLE_VPA);
        }

        $statuses = [self::SUCCESS];

        //Removing this check for now, till we confirm that this is successful status.
//        if ($action === Action::CALLBACK)
//        {
//            array_push($statuses, self::CBS_DOWN);
//        }

        return in_array($status, $statuses, true);
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
