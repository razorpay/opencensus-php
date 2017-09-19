<?php

namespace RZP\Models\Invoice;

class NotifyMedium
{
    const SMS   = 'sms';
    const EMAIL = 'email';

    public static function isMediumValid(string $medium): bool
    {
        $key = __CLASS__ . '::' . strtoupper($medium);

        return ((defined($key) === true) and (constant($key) === $medium));
    }
}
