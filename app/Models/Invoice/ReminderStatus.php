<?php

namespace RZP\Models\Invoice;

use RZP\Exception\BadRequestValidationFailureException;

class ReminderStatus
{
    const PENDING       = 'pending';
    const DISABLED      = 'disabled';
    const COMPLETED     = 'completed';
    const IN_PROGRESS   = 'in_progress';
    const FAILED        = 'failed';

    public static function isStatusValid(string $status): bool
    {
        $key = __CLASS__ . '::' . strtoupper($status);

        return ((defined($key) === true) and (constant($key) === $status));
    }

    public static function checkStatus(string $status)
    {
        if (self::isStatusValid($status) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid status: ' . $status);
        }
    }
}
