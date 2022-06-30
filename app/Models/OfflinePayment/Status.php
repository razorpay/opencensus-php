<?php

namespace RZP\Models\OfflinePayment;

class Status
{
    const CAPTURED = 'captured';
    const PENDING = 'pending';
    const FAILED = 'failed';

    public static function isValid($status)
    {
        return defined(__CLASS__ . '::' . strtoupper($status));
    }
}
