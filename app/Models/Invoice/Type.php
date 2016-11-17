<?php

namespace RZP\Models\Invoice;

class Type
{
    const ECOD = 'ecod';
    const INVOICE = 'invoice';
    const LINK  = 'link';

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
