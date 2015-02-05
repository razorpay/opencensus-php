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

        if (defined(__CLASS__.'::'.strtoupper($type)))
        {
            return $type;
        }
        else
        {
            throw new \InvalidArgumentException('Not a valid type: ' . $type);
        }
    }
}