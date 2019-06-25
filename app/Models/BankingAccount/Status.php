<?php

namespace RZP\Models\BankingAccount;

class Status
{
    const CREATED           = 'created';
    const INITIATED         = 'initiated';
    const PROCESSING        = 'processing';
    const PROCESSED         = 'processed';
    const CANCELLED         = 'cancelled';
    const UNSERVICEABLE     = 'unserviceable';

    public static function isValidStatus(string $status)
    {
        $key = __CLASS__ . '::' . strtoupper($status);

        return ((defined($key) === true) and (constant($key) === $status));
    }

    public static function getAll()
    {
        return [
            self::CREATED,
            self::INITIATED,
            self::PROCESSING,
            self::CANCELLED,
            self::PROCESSED,
            self::UNSERVICEABLE,
        ];
    }
}
