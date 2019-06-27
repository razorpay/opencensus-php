<?php

namespace RZP\Models\BankingAccountStatement;

use RZP\Exception\BadRequestValidationFailureException;

class Type
{
    const CREDIT    = 'credit';
    const DEBIT     = 'debit';

    public static function isValid($type)
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }

    public static function validate($type)
    {
        if (self::isValid($type) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid type: ' . $type);
        }
    }
}
