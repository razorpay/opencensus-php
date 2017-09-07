<?php

namespace RZP\Models\Customer\Token;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class RecurringStatus
{
    /**
     * This status indicates that we are waiting for the bank's response to update the status
     */
    const INITIATED = 'initiated';

    /**
     * This status indicates that this token has been confirmed for future recurring payments
     */
    const CONFIRMED = 'confirmed';

    /**
     * This status indicates that this token has been rejected for future recurring payments
     */
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
                ErrorCode::SERVER_ERROR_TOKEN_INVALID_RECURRING_STATUS,
                Entity::RECURRING_STATUS,
                [
                    'recurring_status' => $recurringStatus
                ]);
        }
    }
}
