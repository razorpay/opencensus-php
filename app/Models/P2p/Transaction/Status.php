<?php

namespace RZP\Models\P2p\Transaction;

class Status
{
    const CREATED    = 'created';
    const COMPLETED  = 'completed';
    const FAILED     = 'failed';

    // Internal Status
    const PENDING    = 'pending';
    const INITIATED  = 'initiated';
    const EXPIRED    = 'expired';
    const REJECTED   = 'rejected';

    public static function isValid(string $key): bool
    {
        return (defined(static::class.'::'.strtoupper($key)));
    }

    public static function map($internalStatus)
    {
        $map = [
            self::CREATED           => self::CREATED,
            self::PENDING           => self::CREATED,
            self::INITIATED         => self::CREATED,

            self::EXPIRED           => self::FAILED,
            self::REJECTED          => self::FAILED,
            self::FAILED            => self::FAILED,

            self::COMPLETED         => self::COMPLETED,
        ];

        return $map[$internalStatus];
    }
}
