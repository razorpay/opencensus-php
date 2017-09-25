<?php

namespace RZP\Models\Payment;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\InvalidArgumentException;

class RecurringType
{
    const INITIAL     = 'initial';
    const AUTO        = 'auto';

    public static function isRecurringTypeValid($type)
    {
        return (defined(__CLASS__ . '::' . strtoupper($type)));
    }

    public static function validateRecurringType($type)
    {
        if (self::isRecurringTypeValid($type) === false)
        {
            throw new InvalidArgumentException(
                null,
                [
                    'field'          => Entity::RECURRING_TYPE,
                    'recurring_type' => $type
                ]);
        }
    }
}