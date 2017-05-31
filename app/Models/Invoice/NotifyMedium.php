<?php

namespace RZP\Models\Invoice;

class NotifyMedium
{
    const SMS   = 'sms';
    const EMAIL = 'email';

    public static function isMediumValid(string $medium): bool
    {
        return (defined(__CLASS__ . '::' . strtoupper($medium)));
    }
}
