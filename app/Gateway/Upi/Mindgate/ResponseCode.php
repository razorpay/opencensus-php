<?php

namespace RZP\Gateway\Upi\Mindgate;

use RZP\Error;
use RZP\Error\ErrorCode;

class ResponseCode
{
    /**
     * We list these as per the docs given to us
     * However, these are never returned in reality
     * @var array
     */
    const CODES = [
        Status::FAILURE           => 'Payment Failed because of Gateway Error',
        Status::FAILED            => 'Payment Failed because of Gateway Error',
        Status::VPA_NOT_AVAILABLE => 'Vpa not available',
        Status::PENDING           => 'Transaction pending',
        Status::TIMEOUT           => 'Transaction timed out',
    ];

    public static function getResponseMessage($code)
    {
        return self::CODES[$code] ?? 'Unknown Gateway Response Code';
    }
}
