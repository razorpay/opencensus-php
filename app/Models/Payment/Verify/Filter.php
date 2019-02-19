<?php

namespace RZP\Models\Payment\Verify;

use RZP\Exception;
use RZP\Error\ErrorCode;

class Filter
{
    const PAYMENTS_CREATED  = 'payments_created';
    const PAYMENTS_FAILED   = 'payments_failed';
    const VERIFY_ERROR      = 'verify_error';
    const VERIFY_FAILED     = 'verify_failed';
    const PAYMENTS_CAPTURED = 'payments_captured';

    protected static $validFilter = [
        self::PAYMENTS_CREATED,
        self::PAYMENTS_FAILED,
        self::PAYMENTS_CAPTURED,
        self::VERIFY_ERROR,
        self::VERIFY_FAILED
    ];

    public static function isValidFilter($filter)
    {
        if (in_array($filter, self::$validFilter) !== true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_PARAMETERS, 'filter', $filter);
        }
    }
}
