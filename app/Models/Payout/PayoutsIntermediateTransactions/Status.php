<?php

namespace RZP\Models\Payout\PayoutsIntermediateTransactions;

use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    const PENDING   = 'pending';
    const COMPLETED = 'completed';
    const REVERSED  = 'reversed';

    public static $timestampedStatuses = [
        self::PENDING,
        self::COMPLETED,
        self::REVERSED,
    ];

    protected static $fromToStatusMap = [
        null => [
            self::PENDING,
        ],
        self::PENDING => [
            self::COMPLETED,
            self::REVERSED
        ],
        self::REVERSED  => [],
        self::COMPLETED => [],
    ];

    public static function isValid(string $status): bool
    {
        $key = __CLASS__ . '::' . strtoupper($status);

        return ((defined($key) === true) and (constant($key) === $status));
    }

    public static function validate(string $status)
    {
        if (self::isValid($status) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid payout intermediate transaction status: ' . $status);
        }
    }

    public static function validateStatusUpdate(string $nextStatus, string $previousStatus = null)
    {
        self::validate($nextStatus);

        $nextStatusList = self::$fromToStatusMap[$previousStatus];

        if (in_array($nextStatus, $nextStatusList, true) === false)
        {
            throw new BadRequestValidationFailureException(
                'Status change not permitted',
                Entity::STATUS,
                [
                    'next_status'  => $nextStatus,
                    'previous_status' => $previousStatus,
                ]);
        }
    }

}
