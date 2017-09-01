<?php

namespace RZP\Models\Customer\Token;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class RecurringStatus
{
    const INITIATED = 'initiated';
    const CONFIRMED = 'confirmed';
    const REJECTED  = 'rejected';

    public static function isRecurringStatusValid($recurringStatus): bool
    {
        return (defined(__CLASS__ . '::' . strtoupper($recurringStatus)));
    }

    public static function validateRecurringStatus($recurringStatus)
    {
        if (self::isRecurringStatusValid($recurringStatus) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_TOKEN_INVALID_RECURRING_STATUS,
                Entity::RECURRING_STATUS,
                [
                    'recurring_status' => $recurringStatus
                ]);
        }
    }
}
