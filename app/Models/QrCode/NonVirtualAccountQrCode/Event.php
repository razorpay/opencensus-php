<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use RZP\Exception\BadRequestValidationFailureException;

class Event
{
    const CREDITED = 'credited';
    const CLOSED   = 'closed';
    const CREATED  = 'created';

    public static function isEventValid(string $event): bool
    {
        $key = __CLASS__ . '::' . strtoupper($event);

        return ((defined($key) === true) and (constant($key) === $event));
    }

    public static function checkEvent(string $event)
    {
        if (self::isEventValid($event) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a event for QR Codes: ' . $event);
        }
    }
}
