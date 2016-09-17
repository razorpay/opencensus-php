<?php

namespace RZP\Models\Card;


class Type
{
    const CREDIT     = 'credit';
    const DEBIT      = 'debit';
    const UNKNOWN    = 'unknown';

    public static function getType($type, $network = null)
    {
        if ($network === Network::AMEX)
        {
            return self::CREDIT;
        }

        if (($network === Network::RUPAY) or
            ($network === Network::MAES))
        {
            return self::DEBIT;
        }

        if (empty($type))
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

    public static function getMaxmindCardType($type)
    {
        if ($type === self::UNKNOWN)
        {
            return 'other';
        }

        return $type . 'card';
    }
}