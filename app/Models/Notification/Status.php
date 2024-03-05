<?php

namespace RZP\Models\Notification;

use RZP\Exception\InvalidArgumentException;

class Status
{
    const CREATED   = 'created';

    const DELIVERED = 'delivered';

    const FAILED  = 'failed';

    public static function isNotificationStatusValid($status): bool
    {
        return (defined(__CLASS__ . '::' . strtoupper($status)));
    }

    public static function validateNotificationStatus($status)
    {
        if (self::isNotificationStatusValid($status) === false)
        {
            throw new InvalidArgumentException(
                'Invalid notification statis',
                [
                    'field'            => Entity::STATUS,
                    'recurring_status' => $status
                ]);
        }
    }
}
