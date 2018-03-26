<?php

namespace RZP\Models\Payment;

use RZP\Exception\InvalidArgumentException;

class RecurringType
{
    const INITIAL       = 'initial';
    const AUTO          = 'auto';
    const CARD_CHANGE   = 'card_change';

    public static function isRecurringTypeValid($type)
    {
        return (defined(__CLASS__ . '::' . strtoupper($type)));
    }

    public static function validateRecurringType($type)
    {
        if (self::isRecurringTypeValid($type) === false)
        {
            throw new InvalidArgumentException(
                'Invalid recurring type',
                [
                    'field'          => Entity::RECURRING_TYPE,
                    'recurring_type' => $type
                ]);
        }
    }
}
