<?php

namespace RZP\Models\Partner\Commission;

use RZP\Exception;

class Status
{
    const CREATED   = 'created';
    const REFUNDED  = 'refunded';
    const CAPTURED  = 'captured';

    /*
     * Allowed next statuses mapping
     */
    const ALLOWED_NEXT_STATUSES_MAPPING = [
        self::CREATED   => [self::CAPTURED, self::REFUNDED],
        self::CAPTURED  => [self::REFUNDED],
        self::REFUNDED  => [],
    ];

    public static function isValidStateTransition(string $current, string $next)
    {
        return (in_array($next, self::ALLOWED_NEXT_STATUSES_MAPPING[$current], true) === true);
    }

    /**
     * @param string $status
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public static function validateStatus(string $status)
    {
        $validStatus = [self::CREATED, self::CAPTURED, self::REFUNDED];

        if (in_array($status, $validStatus, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid status: ' . $status);
        }
    }
}
