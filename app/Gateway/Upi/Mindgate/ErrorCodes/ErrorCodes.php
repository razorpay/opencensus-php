<?php

namespace RZP\Gateway\Upi\Mindgate\ErrorCodes;

use RZP\Gateway\Base;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Mindgate\Status;

class ErrorCodes extends Base\ErrorCodes\Upi\ErrorCodes
{
    public static $statusCodeMap = [
        Status::VPA_NOT_AVAILABLE => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
        Status::FAILURE           => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        Status::FAILED            => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        Status::PENDING           => ErrorCode::BAD_REQUEST_PAYMENT_PENDING,
        Status::TIMEOUT           => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
        Status::EXPIRED           => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
    ];

    public static function getErrorCode($content, $action = Base\Action::REFUND)
    {
        if ($action === Base\Action::CALLBACK)
        {
            self::$errorCodeMap = array_replace(
                self::$errorCodeMap,
                [
                    'NA'  => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                    'RNF' => ErrorCode::GATEWAY_ERROR_PAYMENT_NOT_FOUND,
                ]
            );
        }
        else if ($action === Base\Action::REFUND)
        {
            self::$errorCodeMap = array_replace(
                self::$errorCodeMap,
                [
                    'U48'  => ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING,
                ]
            );
        }

        return self::getInternalErrorCode($content);
    }
}
