<?php

namespace Models\Card;

class Type
{
    const CREDIT     = 'credit';
    const DEBIT      = 'debit';
    const UNKNOWN    = 'unknown';

    public static function getType($type)
    {
        if ($type === '')
        {
            return self::UNKNOWN;
        }

        self::checkType($type);

        return $type;
    }

    public static function checkType($type)
    {
        if (self::isValidType($type) === false)
        {
            throw new \InvalidArgumentException('Not a valid type: ' . $type);
        }
    }

    public static function isValidType($type)
    {
        return (defined(__CLASS__.'::'.strtoupper($type)));
    }
}