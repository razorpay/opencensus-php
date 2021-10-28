<?php

namespace RZP\Models\Store;

use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    const ACTIVE   = 'active';
    const INACTIVE = 'inactive';

    public static function isValid(string $status): bool
    {
        $key = __CLASS__ . '::' . strtoupper($status);

        return ((defined($key) === true) and (constant($key) === $status));
    }

    public static function checkStatus(string $status)
    {
        if (self::isValid($status) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid status: ' . $status);
        }
    }
}
