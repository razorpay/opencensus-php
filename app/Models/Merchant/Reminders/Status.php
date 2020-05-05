<?php

namespace RZP\Models\Merchant\Reminders;

use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    const PENDING       = 'pending';
    const DISABLED      = 'disabled';
    const COMPLETED     = 'completed';
    const IN_PROGRESS   = 'in_progress';
    const FAILED        = 'failed';

    /**
     * @param string $status
     *
     * @return bool
     */
    public static function isStatusValid(string $status): bool
    {
        $key = __CLASS__ . '::' . strtoupper($status);

        return ((defined($key) === true) and (constant($key) === $status));
    }

    /**
     * @param string $status
     *
     * @throws BadRequestValidationFailureException
     */
    public static function checkStatus(string $status)
    {
        if (self::isStatusValid($status) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid reminder status: ' . $status);
        }
    }
}
