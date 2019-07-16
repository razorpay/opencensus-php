<?php

namespace RZP\Models\BankingAccount;

use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    const CREATED           = 'created';
    const INITIATED         = 'initiated';
    const PROCESSING        = 'processing';
    const PROCESSED         = 'processed';
    const CANCELLED         = 'cancelled';
    const ACTIVATED         = 'activated';
    const UNSERVICEABLE     = 'unserviceable';

    protected static $statuses = [
        self::CREATED,
        self::INITIATED,
        self::PROCESSING,
        self::CANCELLED,
        self::PROCESSED,
        self::UNSERVICEABLE,
    ];

    public static function isValidStatus(string $status = null)
    {
        $key = __CLASS__ . '::' . strtoupper($status);

        return ((defined($key) === true) and (constant($key) === $status));
    }

    public static function validate(string $status = null)
    {
        if (self::isValidStatus($status) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid Razorpay Banking status',
                Entity::STATUS,
                [
                    Entity::STATUS => $status
                ]);
        }
    }

    public static function getAll(): array
    {
        return self::$statuses;
    }
}
