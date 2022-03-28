<?php

namespace RZP\Models\OfflineChallan;

use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    const PENDING       = 'pending';
    const VALIDATED     = 'validated';
    const CLOSED        = 'closed';
    const PROCESSED     = 'processed';
    const FAILED        = 'failed';

    protected static $allowedStatus = [
        self::PENDING,
        self::VALIDATED,
        self::CLOSED,
        self::PROCESSED,
        self::FAILED,
    ];

    public static function checkStatus(string $status)
    {
        if (in_array($status,self::$allowedStatus) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid status: ' . $status);
        }
    }
}
