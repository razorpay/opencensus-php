<?php

namespace RZP\Models\Item;

use RZP\Exception\BadRequestValidationFailureException;

class Type
{
    const ADDON        = 'addon';
    const INVOICE      = 'invoice';
    const PLAN         = 'plan';
    const PAYMENT_PAGE = 'payment_page';

    public static function isTypeValid($type)
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }

    public static function checkType($type)
    {
        if (self::isTypeValid($type) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid type: ' . $type);
        }
    }
}
