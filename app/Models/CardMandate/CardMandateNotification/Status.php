<?php

namespace RZP\Models\CardMandate\CardMandateNotification;

use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    const CREATED             = 'created';
    const NOTIFIED            = 'notified';
    const FAILED              = 'failed';
    const PENDING             = 'pending';

    static $stateTran = [
        self::CREATED => [
            self::NOTIFIED,
            self::FAILED,
            self::PENDING,
        ],
        self::PENDING => [
            self::NOTIFIED,
        ],
        self::FAILED => [],
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
