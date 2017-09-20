<?php

namespace RZP\Models\Invoice;

use RZP\Exception\BadRequestValidationFailureException;

class NotifyStatus
{
    const PENDING       = 'pending';
    const SENT          = 'sent';
    const DELIVERED     = 'delivered';
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
