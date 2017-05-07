<?php

namespace RZP\Models\Item;

use RZP\Exception\BadRequestValidationFailureException;

class Type
{
    const ADDON     = 'addon';
    const INVOICE   = 'invoice';
    const PLAN      = 'plan';

    public static function isTypeValid($type)
    {
        return (defined(__CLASS__ . '::' . strtoupper($type)));
    }

    public static function checkType($type)
    {
        if (self::isTypeValid($type) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid type: ' . $type);
        }
    }
}
