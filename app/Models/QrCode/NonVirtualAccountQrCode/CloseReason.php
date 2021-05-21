<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use RZP\Exception\BadRequestValidationFailureException;

class CloseReason
{
    const PAID      = 'paid';
    const EXPIRED   = 'expired';
    const ON_DEMAND = 'on_demand';

    public static function isCloseReasonValid(string $closeReason): bool
    {
        $key = __CLASS__ . '::' . strtoupper($closeReason);

        return ((defined($key) === true) and (constant($key) === $closeReason));
    }

    public static function checkCloseReason(string $closeReason)
    {
        if (self::isCloseReasonValid($closeReason) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid closing reason: ' . $closeReason);
        }
    }
}
