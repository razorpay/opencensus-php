<?php

namespace RZP\Models\Invoice;

class NotifyMedium
{
    const SMS   = 'sms';
    const EMAIL = 'email';

    public static function isMediumValid($medium)
    {
        return (defined(__CLASS__ . '::' . strtoupper($medium)));
    }
}
