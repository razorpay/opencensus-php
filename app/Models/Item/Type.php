<?php

namespace RZP\Models\Item;

class Type
{
    const ADD_ON    = 'add_on';
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
            throw new \InvalidArgumentException('Not a valid type: ' . $type);
        }
    }
}
