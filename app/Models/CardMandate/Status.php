<?php

namespace RZP\Models\CardMandate;

use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    const CREATED           = 'created';
    const MANDATE_APPROVED  = 'mandate_approved';
    const MANDATE_CANCELLED = 'mandate_cancelled'; // cancelled while registering itself
    const ACTIVE            = 'active';
    const PAUSED            = 'paused';
    const CANCELLED         = 'cancelled'; // cancelled after mandate is active or paused.
    const COMPLETED         = 'completed';

    static $stateTran = [
        self::CREATED => [
            self::MANDATE_APPROVED,
            self::MANDATE_CANCELLED,
            self::ACTIVE,
        ],
        self::MANDATE_APPROVED => [
            self::ACTIVE,
        ],
        self::ACTIVE => [
            self::PAUSED,
            self::CANCELLED,
            self::COMPLETED,
        ],
        self::PAUSED => [
            self::ACTIVE,
            self::CANCELLED,
            self::COMPLETED,
        ],
        self::MANDATE_CANCELLED => [],
        self::CANCELLED => [],
        self::COMPLETED => [],
    ];

    public static function isValid(string $status): bool
    {
        $key = __CLASS__ . '::' . strtoupper($status);

        return ((defined($key) === true) and (constant($key) === $status));
    }

    public static function checkStatus(string $status)
    {
        if (self::isValid($status) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid status: ' . $status);
        }
    }

    public static function checkStatusChange(string $previousState, string $status)
    {
        $tran = self::$stateTran[$previousState] ?? [];

        if (empty($previousState) === true)
        {
            if ($status !== self::CREATED)
            {
                throw new BadRequestValidationFailureException(
                    'Not a valid initial status: ' . $status
                );
            }
        }
        else if (in_array($status, $tran) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid status change from ' . $previousState . ' to ' . $status
            );
        }
    }
}
