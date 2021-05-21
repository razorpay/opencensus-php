<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    const ACTIVE = 'active';
    const CLOSED = 'closed';

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
