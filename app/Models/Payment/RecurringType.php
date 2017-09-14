<?php

namespace RZP\Models\Payment;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class RecurringType
{
    const REGISTRATION      = 'registration';
    const DEBIT             = 'debit';

    public static function isRecurringTypeValid($type)
    {
        return (defined(__CLASS__ . '::' . strtoupper($type)));
    }

    public static function validateRecurringType($type)
    {
        if (self::isRecurringTypeValid($type) === false)
        {
            throw new BadRequestException(
                ErrorCode::SERVER_ERROR_PAYMENT_INVALID_RECURRING_TYPE,
                Entity::RECURRING_TYPE,
                [
                    'recurring_type' => $type
                ]);
        }
    }
}