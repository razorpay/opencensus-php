<?php

namespace RZP\Models\UpiMandate;

class RecurringType
{
    const ON     = 'on';
    const BEFORE = 'before';
    const AFTER  = 'after';

    public static function isValid($recurringType)
    {
        return (defined(RecurringType::class.'::'.strtoupper($recurringType)));
    }
}
